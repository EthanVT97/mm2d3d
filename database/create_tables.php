<?php
include_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->connect();
    
    // Enable PostgreSQL specific optimizations
    $db->exec("SET synchronous_commit = off"); // Improves write performance
    $db->exec("SET work_mem = '32MB'"); // Increase working memory for complex queries
    $db->exec("SET maintenance_work_mem = '128MB'"); // Increase memory for maintenance operations
    
    // Start transaction for table creation
    $db->beginTransaction();
    
    // Create tables
    $tables = array();
    
    // Users table with optimizations
    $tables[] = "
    CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        phone_number VARCHAR(20) UNIQUE NOT NULL,
        name VARCHAR(100),
        balance DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'active',
        risk_level VARCHAR(20) DEFAULT 'low',
        last_login TIMESTAMP
    ) WITH (fillfactor = 90);
    CREATE INDEX IF NOT EXISTS idx_users_phone ON users USING btree (phone_number);
    CREATE INDEX IF NOT EXISTS idx_users_status ON users USING hash (status);
    CREATE INDEX IF NOT EXISTS idx_users_risk ON users USING hash (risk_level);
    CLUSTER users USING idx_users_phone;";
    
    // Agents table
    $tables[] = "
    CREATE TABLE IF NOT EXISTS agents (
        id SERIAL PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        balance DECIMAL(10,2) DEFAULT 0.00,
        created_by INTEGER REFERENCES users(id),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'active',
        last_login TIMESTAMP,
        commission_rate DECIMAL(5,2) DEFAULT 0.00
    ) WITH (fillfactor = 90);
    CREATE INDEX IF NOT EXISTS idx_agents_username ON agents USING btree (username);
    CREATE INDEX IF NOT EXISTS idx_agents_status ON agents USING hash (status);";
    
    // Transactions table with partitioning
    $tables[] = "
    CREATE TABLE IF NOT EXISTS transactions (
        id SERIAL PRIMARY KEY,
        user_or_agent_id INTEGER NOT NULL,
        type VARCHAR(20) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'pending',
        reference_number VARCHAR(50)
    ) PARTITION BY RANGE (date);
    
    CREATE TABLE IF NOT EXISTS transactions_current_month 
    PARTITION OF transactions 
    FOR VALUES FROM (DATE_TRUNC('month', CURRENT_DATE)) 
    TO (DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month');
    
    CREATE TABLE IF NOT EXISTS transactions_previous_month 
    PARTITION OF transactions 
    FOR VALUES FROM (DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month')) 
    TO (DATE_TRUNC('month', CURRENT_DATE));
    
    CREATE INDEX IF NOT EXISTS idx_transactions_user ON transactions USING btree (user_or_agent_id);
    CREATE INDEX IF NOT EXISTS idx_transactions_date ON transactions USING btree (date);
    CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions USING hash (status);";
    
    // Playbets table with partitioning
    $tables[] = "
    CREATE TABLE IF NOT EXISTS playbets (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        lottery_type VARCHAR(20) NOT NULL,
        number_selected VARCHAR(20) NOT NULL,
        bet_amount DECIMAL(10,2) NOT NULL,
        result VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        draw_date DATE NOT NULL,
        winning_amount DECIMAL(10,2) DEFAULT 0.00,
        commission_amount DECIMAL(10,2) DEFAULT 0.00,
        settlement_status VARCHAR(20) DEFAULT 'pending'
    ) PARTITION BY RANGE (created_at);
    
    CREATE TABLE IF NOT EXISTS playbets_current_month 
    PARTITION OF playbets 
    FOR VALUES FROM (DATE_TRUNC('month', CURRENT_DATE)) 
    TO (DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month');
    
    CREATE TABLE IF NOT EXISTS playbets_previous_month 
    PARTITION OF playbets 
    FOR VALUES FROM (DATE_TRUNC('month', CURRENT_DATE - INTERVAL '1 month')) 
    TO (DATE_TRUNC('month', CURRENT_DATE));
    
    CREATE INDEX IF NOT EXISTS idx_playbets_user ON playbets USING btree (user_id);
    CREATE INDEX IF NOT EXISTS idx_playbets_date ON playbets USING btree (created_at);
    CREATE INDEX IF NOT EXISTS idx_playbets_lottery ON playbets USING hash (lottery_type);
    CREATE INDEX IF NOT EXISTS idx_playbets_result ON playbets USING hash (result);";
    
    // Results table
    $tables[] = "
    CREATE TABLE IF NOT EXISTS results (
        id SERIAL PRIMARY KEY,
        lottery_type VARCHAR(20) NOT NULL,
        winning_numbers VARCHAR(20) NOT NULL,
        draw_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE INDEX IF NOT EXISTS idx_results_date ON results USING btree (draw_date);
    CREATE INDEX IF NOT EXISTS idx_results_lottery ON results USING hash (lottery_type);";
    
    // User sessions table
    $tables[] = "
    CREATE TABLE IF NOT EXISTS user_sessions (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        device_id VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) PARTITION BY RANGE (created_at);
    
    CREATE TABLE IF NOT EXISTS user_sessions_current_month 
    PARTITION OF user_sessions 
    FOR VALUES FROM (DATE_TRUNC('month', CURRENT_DATE)) 
    TO (DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month');
    
    CREATE INDEX IF NOT EXISTS idx_sessions_user ON user_sessions USING btree (user_id);
    CREATE INDEX IF NOT EXISTS idx_sessions_device ON user_sessions USING hash (device_id);
    CREATE INDEX IF NOT EXISTS idx_sessions_date ON user_sessions USING btree (created_at);";
    
    // User preferences table
    $tables[] = "
    CREATE TABLE IF NOT EXISTS user_preferences (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        lottery_type VARCHAR(20),
        preferred_numbers TEXT[],
        preferred_bet_amount DECIMAL(10,2),
        preferred_time_slots INTEGER[],
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE INDEX IF NOT EXISTS idx_preferences_user ON user_preferences USING btree (user_id);";
    
    // Risk assessment table
    $tables[] = "
    CREATE TABLE IF NOT EXISTS risk_assessments (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        risk_level VARCHAR(20),
        risk_factors TEXT[],
        assessment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        assessed_by INTEGER REFERENCES agents(id),
        notes TEXT
    );
    CREATE INDEX IF NOT EXISTS idx_risk_user ON risk_assessments USING btree (user_id);
    CREATE INDEX IF NOT EXISTS idx_risk_date ON risk_assessments USING btree (assessment_date);
    CREATE INDEX IF NOT EXISTS idx_risk_level ON risk_assessments USING hash (risk_level);";
    
    // Alerts table with partitioning
    $tables[] = "
    CREATE TABLE IF NOT EXISTS alerts (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        agent_id INTEGER REFERENCES agents(id),
        alert_type VARCHAR(50),
        severity VARCHAR(20),
        message TEXT,
        is_resolved BOOLEAN DEFAULT false,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP
    ) PARTITION BY RANGE (created_at);
    
    CREATE TABLE IF NOT EXISTS alerts_current_month 
    PARTITION OF alerts 
    FOR VALUES FROM (DATE_TRUNC('month', CURRENT_DATE)) 
    TO (DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month');
    
    CREATE INDEX IF NOT EXISTS idx_alerts_user ON alerts USING btree (user_id);
    CREATE INDEX IF NOT EXISTS idx_alerts_agent ON alerts USING btree (agent_id);
    CREATE INDEX IF NOT EXISTS idx_alerts_type ON alerts USING hash (alert_type);
    CREATE INDEX IF NOT EXISTS idx_alerts_severity ON alerts USING hash (severity);
    CREATE INDEX IF NOT EXISTS idx_alerts_status ON alerts USING hash (is_resolved);";
    
    // User analytics table
    $tables[] = "
    CREATE TABLE IF NOT EXISTS user_analytics (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id),
        total_bets INTEGER DEFAULT 0,
        total_wins INTEGER DEFAULT 0,
        total_losses INTEGER DEFAULT 0,
        total_deposits DECIMAL(10,2) DEFAULT 0,
        total_withdrawals DECIMAL(10,2) DEFAULT 0,
        last_bet_date TIMESTAMP,
        last_transaction_date TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    CREATE INDEX IF NOT EXISTS idx_analytics_user ON user_analytics USING btree (user_id);
    CREATE INDEX IF NOT EXISTS idx_analytics_bets ON user_analytics USING btree (total_bets);";
    
    // Create tables
    foreach ($tables as $table_sql) {
        $db->exec($table_sql);
        echo "✓ Table created successfully\n";
    }
    
    // Create triggers and functions
    $triggers = array();
    
    // Update user analytics trigger
    $triggers[] = "
    CREATE OR REPLACE FUNCTION update_user_analytics()
    RETURNS TRIGGER AS $$
    BEGIN
        INSERT INTO user_analytics (user_id)
        VALUES (NEW.user_id)
        ON CONFLICT (user_id) DO UPDATE
        SET 
            total_bets = user_analytics.total_bets + 1,
            total_wins = CASE WHEN NEW.result = 'win' 
                             THEN user_analytics.total_wins + 1 
                             ELSE user_analytics.total_wins END,
            total_losses = CASE WHEN NEW.result = 'lose' 
                               THEN user_analytics.total_losses + 1 
                               ELSE user_analytics.total_losses END,
            last_bet_date = NEW.created_at,
            updated_at = CURRENT_TIMESTAMP;
        RETURN NEW;
    END;
    $$ LANGUAGE plpgsql;
    
    DROP TRIGGER IF EXISTS update_analytics_on_bet ON playbets;
    CREATE TRIGGER update_analytics_on_bet
    AFTER INSERT ON playbets
    FOR EACH ROW
    EXECUTE FUNCTION update_user_analytics();";
    
    // Update transaction analytics trigger
    $triggers[] = "
    CREATE OR REPLACE FUNCTION update_transaction_analytics()
    RETURNS TRIGGER AS $$
    BEGIN
        UPDATE user_analytics
        SET 
            total_deposits = CASE WHEN NEW.type = 'deposit' 
                                THEN total_deposits + NEW.amount 
                                ELSE total_deposits END,
            total_withdrawals = CASE WHEN NEW.type = 'withdraw' 
                                   THEN total_withdrawals + NEW.amount 
                                   ELSE total_withdrawals END,
            last_transaction_date = NEW.date,
            updated_at = CURRENT_TIMESTAMP
        WHERE user_id = NEW.user_or_agent_id;
        RETURN NEW;
    END;
    $$ LANGUAGE plpgsql;
    
    DROP TRIGGER IF EXISTS update_analytics_on_transaction ON transactions;
    CREATE TRIGGER update_analytics_on_transaction
    AFTER INSERT ON transactions
    FOR EACH ROW
    EXECUTE FUNCTION update_transaction_analytics();";
    
    // Create triggers
    foreach ($triggers as $trigger_sql) {
        $db->exec($trigger_sql);
        echo "✓ Trigger created successfully\n";
    }
    
    // Commit transaction
    $db->commit();
    
    echo "✓ All tables and triggers created successfully!\n";
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
