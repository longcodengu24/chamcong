<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION calculate_price_amount() RETURNS TRIGGER AS $$
            DECLARE
                rate INTEGER;
            BEGIN
                IF NEW.check_in IS NOT NULL AND NEW.check_out IS NOT NULL AND NEW.user_id IS NOT NULL THEN
                    SELECT hourly_rate INTO rate FROM "user" WHERE id = NEW.user_id;
                    NEW.amount := ROUND(EXTRACT(EPOCH FROM (NEW.check_out - NEW.check_in)) / 3600.0 * COALESCE(rate, 0));
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_price_amount
                BEFORE INSERT OR UPDATE ON price
                FOR EACH ROW
                EXECUTE FUNCTION calculate_price_amount();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_price_amount ON price;');
        DB::unprepared('DROP FUNCTION IF EXISTS calculate_price_amount();');
    }
};
