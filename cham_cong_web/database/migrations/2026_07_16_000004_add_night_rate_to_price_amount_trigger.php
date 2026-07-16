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
                cursor_ts TIMESTAMP;
                segment_end TIMESTAMP;
                segment_hours NUMERIC;
                is_night BOOLEAN;
                total NUMERIC := 0;
            BEGIN
                IF NEW.check_in IS NOT NULL AND NEW.check_out IS NOT NULL AND NEW.user_id IS NOT NULL THEN
                    SELECT hourly_rate INTO rate FROM "user" WHERE id = NEW.user_id;
                    rate := COALESCE(rate, 0);

                    -- Chia ca làm thành từng đoạn theo từng giờ để áp giá đêm
                    -- (22h-4h, x1.2) khác giá ngày (4h-22h) khi ca xuyên nhiều giờ/qua đêm.
                    cursor_ts := NEW.check_in;
                    WHILE cursor_ts < NEW.check_out LOOP
                        segment_end := LEAST(NEW.check_out, date_trunc('hour', cursor_ts) + interval '1 hour');
                        is_night := EXTRACT(HOUR FROM cursor_ts) >= 22 OR EXTRACT(HOUR FROM cursor_ts) < 4;
                        segment_hours := EXTRACT(EPOCH FROM (segment_end - cursor_ts)) / 3600.0;
                        total := total + segment_hours * rate * (CASE WHEN is_night THEN 1.2 ELSE 1 END);
                        cursor_ts := segment_end;
                    END LOOP;

                    NEW.amount := ROUND(total);
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }

    public function down(): void
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
        SQL);
    }
};
