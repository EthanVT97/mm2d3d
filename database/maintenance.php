<?php
include_once '../config/database.php';

class DatabaseMaintenance {
    private $db;
    private $maintenance_log = [];

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message";
        $this->maintenance_log[] = $log_entry;
        echo $log_entry . "\n";
    }

    public function createPartitions() {
        try {
            // Create next month's partitions
            $tables = ['transactions', 'playbets', 'alerts', 'user_sessions'];
            $next_month = date('Y-m-01', strtotime('first day of next month'));
            $month_after = date('Y-m-01', strtotime('first day of next month +1 month'));

            foreach ($tables as $table) {
                $partition_name = strtolower($table) . "_" . date('Y_m', strtotime('first day of next month'));
                $sql = "
                CREATE TABLE IF NOT EXISTS {$partition_name}
                PARTITION OF {$table}
                FOR VALUES FROM ('$next_month') TO ('$month_after')";
                
                $this->db->exec($sql);
                $this->log("Created partition {$partition_name}");
            }
        } catch (PDOException $e) {
            $this->log("Error creating partitions: " . $e->getMessage());
        }
    }

    public function optimizeTables() {
        try {
            $tables = [
                'users', 'agents', 'transactions', 'playbets', 
                'results', 'user_sessions', 'user_preferences',
                'risk_assessments', 'alerts', 'user_analytics'
            ];

            foreach ($tables as $table) {
                // Analyze table for better query planning
                $this->db->exec("ANALYZE {$table}");
                $this->log("Analyzed table {$table}");

                // Vacuum table to reclaim storage and update statistics
                $this->db->exec("VACUUM (ANALYZE, VERBOSE) {$table}");
                $this->log("Vacuumed table {$table}");

                // Cluster table based on primary index
                $this->db->exec("CLUSTER VERBOSE {$table}");
                $this->log("Clustered table {$table}");
            }
        } catch (PDOException $e) {
            $this->log("Error optimizing tables: " . $e->getMessage());
        }
    }

    public function createAdditionalIndexes() {
        try {
            $indexes = [
                // Users table indexes
                "CREATE INDEX IF NOT EXISTS idx_users_created_at ON users USING btree (created_at)",
                "CREATE INDEX IF NOT EXISTS idx_users_balance ON users USING btree (balance)",
                "CREATE INDEX IF NOT EXISTS idx_users_composite ON users USING btree (status, risk_level, created_at)",
                
                // Transactions table indexes
                "CREATE INDEX IF NOT EXISTS idx_transactions_amount ON transactions USING btree (amount)",
                "CREATE INDEX IF NOT EXISTS idx_transactions_composite ON transactions USING btree (user_or_agent_id, type, date)",
                "CREATE INDEX IF NOT EXISTS idx_transactions_reference ON transactions USING hash (reference_number)",
                
                // Playbets table indexes
                "CREATE INDEX IF NOT EXISTS idx_playbets_amount ON playbets USING btree (bet_amount)",
                "CREATE INDEX IF NOT EXISTS idx_playbets_composite ON playbets USING btree (user_id, lottery_type, created_at)",
                "CREATE INDEX IF NOT EXISTS idx_playbets_winning ON playbets USING btree (winning_amount)",
                
                // User sessions table indexes
                "CREATE INDEX IF NOT EXISTS idx_sessions_composite ON user_sessions USING btree (user_id, created_at)",
                "CREATE INDEX IF NOT EXISTS idx_sessions_ip ON user_sessions USING hash (ip_address)",
                
                // Risk assessments table indexes
                "CREATE INDEX IF NOT EXISTS idx_risk_composite ON risk_assessments USING btree (user_id, risk_level, assessment_date)",
                
                // Alerts table indexes
                "CREATE INDEX IF NOT EXISTS idx_alerts_composite ON alerts USING btree (user_id, alert_type, created_at)",
                "CREATE INDEX IF NOT EXISTS idx_alerts_resolution ON alerts USING btree (is_resolved, resolved_at)"
            ];

            foreach ($indexes as $index) {
                $this->db->exec($index);
                $this->log("Created index: " . explode(" ON ", $index)[0]);
            }
        } catch (PDOException $e) {
            $this->log("Error creating indexes: " . $e->getMessage());
        }
    }

    public function archiveOldData() {
        try {
            $archive_date = date('Y-m-d', strtotime('-6 months'));
            
            // Create archive tables if they don't exist
            $archive_tables = [
                "CREATE TABLE IF NOT EXISTS transactions_archive (LIKE transactions INCLUDING ALL)",
                "CREATE TABLE IF NOT EXISTS playbets_archive (LIKE playbets INCLUDING ALL)",
                "CREATE TABLE IF NOT EXISTS alerts_archive (LIKE alerts INCLUDING ALL)",
                "CREATE TABLE IF NOT EXISTS user_sessions_archive (LIKE user_sessions INCLUDING ALL)"
            ];

            foreach ($archive_tables as $sql) {
                $this->db->exec($sql);
                $this->log("Created archive table: " . explode(" (", $sql)[0]);
            }

            // Move old data to archive tables
            $archive_queries = [
                "INSERT INTO transactions_archive 
                SELECT * FROM transactions 
                WHERE date < :archive_date",

                "INSERT INTO playbets_archive 
                SELECT * FROM playbets 
                WHERE created_at < :archive_date",

                "INSERT INTO alerts_archive 
                SELECT * FROM alerts 
                WHERE created_at < :archive_date",

                "INSERT INTO user_sessions_archive 
                SELECT * FROM user_sessions 
                WHERE created_at < :archive_date"
            ];

            $this->db->beginTransaction();

            foreach ($archive_queries as $sql) {
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':archive_date', $archive_date);
                $stmt->execute();
                $this->log("Archived data: " . $stmt->rowCount() . " rows");
            }

            // Delete archived data from main tables
            $delete_queries = [
                "DELETE FROM transactions WHERE date < :archive_date",
                "DELETE FROM playbets WHERE created_at < :archive_date",
                "DELETE FROM alerts WHERE created_at < :archive_date",
                "DELETE FROM user_sessions WHERE created_at < :archive_date"
            ];

            foreach ($delete_queries as $sql) {
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':archive_date', $archive_date);
                $stmt->execute();
                $this->log("Deleted archived data: " . $stmt->rowCount() . " rows");
            }

            $this->db->commit();
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->log("Error archiving data: " . $e->getMessage());
        }
    }

    public function createMaintenanceProcedures() {
        try {
            // Create maintenance procedures
            $procedures = [
                // Procedure to update table statistics
                "CREATE OR REPLACE PROCEDURE update_table_stats()
                LANGUAGE plpgsql
                AS $$
                BEGIN
                    ANALYZE VERBOSE;
                END;
                $$",

                // Procedure to clean up expired sessions
                "CREATE OR REPLACE PROCEDURE cleanup_expired_sessions()
                LANGUAGE plpgsql
                AS $$
                BEGIN
                    DELETE FROM user_sessions 
                    WHERE last_active < CURRENT_TIMESTAMP - INTERVAL '24 hours';
                END;
                $$",

                // Procedure to rotate partitions
                "CREATE OR REPLACE PROCEDURE rotate_partitions()
                LANGUAGE plpgsql
                AS $$
                DECLARE
                    next_month date;
                    partition_name text;
                BEGIN
                    next_month := date_trunc('month', CURRENT_DATE + INTERVAL '1 month');
                    
                    -- Create next month's partitions
                    FOR partition_name IN 
                        SELECT tablename 
                        FROM pg_tables 
                        WHERE tablename LIKE '%_partition'
                    LOOP
                        EXECUTE format(
                            'CREATE TABLE IF NOT EXISTS %I PARTITION OF %I
                            FOR VALUES FROM (%L) TO (%L)',
                            partition_name || '_' || to_char(next_month, 'YYYY_MM'),
                            partition_name,
                            next_month,
                            next_month + INTERVAL '1 month'
                        );
                    END LOOP;
                END;
                $$"
            ];

            foreach ($procedures as $procedure) {
                $this->db->exec($procedure);
                $this->log("Created procedure: " . substr($procedure, 0, strpos($procedure, '(')));
            }
        } catch (PDOException $e) {
            $this->log("Error creating procedures: " . $e->getMessage());
        }
    }

    public function optimizeQueries() {
        try {
            // Create materialized views for common queries
            $views = [
                // User statistics view
                "CREATE MATERIALIZED VIEW IF NOT EXISTS mv_user_statistics AS
                SELECT 
                    u.id,
                    u.name,
                    COUNT(DISTINCT p.id) as total_bets,
                    COALESCE(SUM(p.bet_amount), 0) as total_bet_amount,
                    COUNT(DISTINCT CASE WHEN p.result = 'win' THEN p.id END) as winning_bets,
                    COALESCE(SUM(CASE WHEN p.result = 'win' THEN p.winning_amount ELSE 0 END), 0) as total_winnings
                FROM users u
                LEFT JOIN playbets p ON u.id = p.user_id
                GROUP BY u.id, u.name
                WITH DATA",

                // Agent performance view
                "CREATE MATERIALIZED VIEW IF NOT EXISTS mv_agent_performance AS
                SELECT 
                    a.id,
                    a.username,
                    COUNT(DISTINCT u.id) as total_users,
                    COUNT(DISTINCT p.id) as total_bets,
                    COALESCE(SUM(p.commission_amount), 0) as total_commission
                FROM agents a
                LEFT JOIN users u ON a.id = u.created_by
                LEFT JOIN playbets p ON u.id = p.user_id
                GROUP BY a.id, a.username
                WITH DATA"
            ];

            foreach ($views as $view) {
                $this->db->exec($view);
                $this->log("Created materialized view: " . substr($view, strpos($view, 'mv_'), strpos($view, ' AS')));
            }

            // Create refresh function for materialized views
            $refresh_function = "
            CREATE OR REPLACE FUNCTION refresh_materialized_views()
            RETURNS void AS $$
            BEGIN
                REFRESH MATERIALIZED VIEW CONCURRENTLY mv_user_statistics;
                REFRESH MATERIALIZED VIEW CONCURRENTLY mv_agent_performance;
            END;
            $$ LANGUAGE plpgsql;";

            $this->db->exec($refresh_function);
            $this->log("Created refresh function for materialized views");

        } catch (PDOException $e) {
            $this->log("Error optimizing queries: " . $e->getMessage());
        }
    }

    public function runMaintenance() {
        $this->log("Starting database maintenance...");
        
        $this->createPartitions();
        $this->optimizeTables();
        $this->createAdditionalIndexes();
        $this->archiveOldData();
        $this->createMaintenanceProcedures();
        $this->optimizeQueries();
        
        $this->log("Database maintenance completed.");
    }
}

// Run maintenance
$maintenance = new DatabaseMaintenance();
$maintenance->runMaintenance();
?>
