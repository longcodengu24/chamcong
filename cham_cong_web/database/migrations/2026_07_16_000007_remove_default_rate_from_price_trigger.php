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
                schedule JSONB;
                entry JSONB;
                day_cursor DATE;
                last_day DATE;
                entry_from TIME;
                entry_to TIME;
                entry_rate NUMERIC;
                seg_start TIMESTAMP;
                seg_end TIMESTAMP;
                ov_start TIMESTAMP;
                ov_end TIMESTAMP;
                total NUMERIC := 0;
            BEGIN
                IF NEW.check_in IS NOT NULL AND NEW.check_out IS NOT NULL AND NEW.user_id IS NOT NULL THEN
                    SELECT COALESCE(rate_schedule, '[]'::jsonb) INTO schedule FROM "user" WHERE id = NEW.user_id;

                    day_cursor := date_trunc('day', NEW.check_in)::date;
                    last_day := date_trunc('day', NEW.check_out)::date;

                    -- Chỉ tính tiền cho phần thời gian rơi vào 1 khung giờ đã khai báo.
                    -- Giờ nào không nằm trong khung nào thì không được trả (không còn giá mặc định).
                    WHILE day_cursor <= last_day LOOP
                        FOR entry IN SELECT * FROM jsonb_array_elements(schedule) LOOP
                            entry_from := (entry->>'from')::time;
                            entry_to := (entry->>'to')::time;
                            entry_rate := (entry->>'rate')::numeric;

                            IF entry_from <= entry_to THEN
                                seg_start := day_cursor + entry_from;
                                seg_end := day_cursor + entry_to;
                                ov_start := GREATEST(seg_start, NEW.check_in);
                                ov_end := LEAST(seg_end, NEW.check_out);
                                IF ov_start < ov_end THEN
                                    total := total + EXTRACT(EPOCH FROM (ov_end - ov_start)) / 3600.0 * entry_rate;
                                END IF;
                            ELSE
                                -- Khung qua đêm (vd 22:00-06:00): tách thành [from, 24h) và [0h, to)
                                seg_start := day_cursor + entry_from;
                                seg_end := day_cursor + interval '1 day';
                                ov_start := GREATEST(seg_start, NEW.check_in);
                                ov_end := LEAST(seg_end, NEW.check_out);
                                IF ov_start < ov_end THEN
                                    total := total + EXTRACT(EPOCH FROM (ov_end - ov_start)) / 3600.0 * entry_rate;
                                END IF;

                                seg_start := day_cursor;
                                seg_end := day_cursor + entry_to;
                                ov_start := GREATEST(seg_start, NEW.check_in);
                                ov_end := LEAST(seg_end, NEW.check_out);
                                IF ov_start < ov_end THEN
                                    total := total + EXTRACT(EPOCH FROM (ov_end - ov_start)) / 3600.0 * entry_rate;
                                END IF;
                            END IF;
                        END LOOP;

                        day_cursor := day_cursor + 1;
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
                default_rate NUMERIC;
                schedule JSONB;
                entry JSONB;
                day_cursor DATE;
                last_day DATE;
                entry_from TIME;
                entry_to TIME;
                entry_rate NUMERIC;
                seg_start TIMESTAMP;
                seg_end TIMESTAMP;
                ov_start TIMESTAMP;
                ov_end TIMESTAMP;
                total NUMERIC := 0;
                covered_seconds NUMERIC := 0;
                shift_seconds NUMERIC;
                gap_seconds NUMERIC;
            BEGIN
                IF NEW.check_in IS NOT NULL AND NEW.check_out IS NOT NULL AND NEW.user_id IS NOT NULL THEN
                    SELECT hourly_rate, COALESCE(rate_schedule, '[]'::jsonb)
                        INTO default_rate, schedule
                        FROM "user" WHERE id = NEW.user_id;
                    default_rate := COALESCE(default_rate, 0);

                    day_cursor := date_trunc('day', NEW.check_in)::date;
                    last_day := date_trunc('day', NEW.check_out)::date;

                    WHILE day_cursor <= last_day LOOP
                        FOR entry IN SELECT * FROM jsonb_array_elements(schedule) LOOP
                            entry_from := (entry->>'from')::time;
                            entry_to := (entry->>'to')::time;
                            entry_rate := (entry->>'rate')::numeric;

                            IF entry_from <= entry_to THEN
                                seg_start := day_cursor + entry_from;
                                seg_end := day_cursor + entry_to;
                                ov_start := GREATEST(seg_start, NEW.check_in);
                                ov_end := LEAST(seg_end, NEW.check_out);
                                IF ov_start < ov_end THEN
                                    total := total + EXTRACT(EPOCH FROM (ov_end - ov_start)) / 3600.0 * entry_rate;
                                    covered_seconds := covered_seconds + EXTRACT(EPOCH FROM (ov_end - ov_start));
                                END IF;
                            ELSE
                                seg_start := day_cursor + entry_from;
                                seg_end := day_cursor + interval '1 day';
                                ov_start := GREATEST(seg_start, NEW.check_in);
                                ov_end := LEAST(seg_end, NEW.check_out);
                                IF ov_start < ov_end THEN
                                    total := total + EXTRACT(EPOCH FROM (ov_end - ov_start)) / 3600.0 * entry_rate;
                                    covered_seconds := covered_seconds + EXTRACT(EPOCH FROM (ov_end - ov_start));
                                END IF;

                                seg_start := day_cursor;
                                seg_end := day_cursor + entry_to;
                                ov_start := GREATEST(seg_start, NEW.check_in);
                                ov_end := LEAST(seg_end, NEW.check_out);
                                IF ov_start < ov_end THEN
                                    total := total + EXTRACT(EPOCH FROM (ov_end - ov_start)) / 3600.0 * entry_rate;
                                    covered_seconds := covered_seconds + EXTRACT(EPOCH FROM (ov_end - ov_start));
                                END IF;
                            END IF;
                        END LOOP;

                        day_cursor := day_cursor + 1;
                    END LOOP;

                    shift_seconds := EXTRACT(EPOCH FROM (NEW.check_out - NEW.check_in));
                    gap_seconds := GREATEST(shift_seconds - covered_seconds, 0);
                    total := total + gap_seconds / 3600.0 * default_rate;

                    NEW.amount := ROUND(total);
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);
    }
};
