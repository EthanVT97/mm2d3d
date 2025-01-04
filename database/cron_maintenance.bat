@echo off
echo Starting database maintenance at %date% %time%

REM Run PHP maintenance script
php "c:/Users/Acer/Desktop/2ddd/database/maintenance.php"

REM Run database specific maintenance
psql "host=aws-0-ap-southeast-1.pooler.supabase.com port=6543 dbname=postgres user=postgres.jaubdheyosmukdxvctbq password=admin123" -c "CALL update_table_stats();"
psql "host=aws-0-ap-southeast-1.pooler.supabase.com port=6543 dbname=postgres user=postgres.jaubdheyosmukdxvctbq password=admin123" -c "CALL cleanup_expired_sessions();"
psql "host=aws-0-ap-southeast-1.pooler.supabase.com port=6543 dbname=postgres user=postgres.jaubdheyosmukdxvctbq password=admin123" -c "CALL rotate_partitions();"
psql "host=aws-0-ap-southeast-1.pooler.supabase.com port=6543 dbname=postgres user=postgres.jaubdheyosmukdxvctbq password=admin123" -c "SELECT refresh_materialized_views();"

echo Maintenance completed at %date% %time%
