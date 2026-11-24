<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline = the Laravel 7 SQLite schema, copied verbatim from sqlite_master of the
 * production database (17 tables + their indexes; it has no triggers or views). Parents first.
 *
 * - Fresh database: creates everything.
 * - Existing Laravel 7 database (all tables present): does nothing, it is only recorded
 *   in `migrations` next to the old Laravel 7 entries.
 * - Some tables present but not all: stops without touching anything.
 *
 * Never edit the SQL below; schema changes go in new migrations.
 */
return new class extends Migration
{
    /** @var list<string> */
    public const TABLES = [
        'departments',
        'users',
        'sub_departments',
        'master_archives',
        'warehouses',
        'warehouse_locations',
        'numbering_formats',
        'archives',
        'archive_items',
        'warehouse_rack_slots',
        'warehouse_entry_logs',
        'borrowing_logs',
        'destruction_logs',
        'archive_extension_logs',
        'activity_logs',
        'app_settings',
        'system_events',
    ];

    public function up(): void
    {
        $existing = array_values(array_filter(self::TABLES, fn (string $table): bool => Schema::hasTable($table)));

        if (count($existing) === count(self::TABLES)) {
            return;
        }

        if ($existing !== []) {
            throw new RuntimeException('Database has only part of the DMS schema ('.implode(', ', $existing).'). Restore a backup instead of migrating.');
        }

        DB::unprepared(<<<'SQL'
CREATE TABLE "departments" ("id" integer not null primary key autoincrement, "code" varchar not null, "name" varchar not null, "description" text null, "created_at" datetime null, "updated_at" datetime null, "sidar_id" varchar null, "retention_years" integer not null default '5', "is_active" tinyint(1) not null default '1');

CREATE TABLE "users" ("id" integer not null primary key autoincrement, "name" varchar not null, "email" varchar not null, "email_verified_at" datetime null, "password" varchar not null, "remember_token" varchar null, "created_at" datetime null, "updated_at" datetime null, "department_id" integer null, "role" varchar check ("role" in ('admin', 'pic_dept', 'pic_gudang')) not null default 'pic_dept', "phone" varchar null);

CREATE TABLE "sub_departments" ("id" integer not null primary key autoincrement, "sidar_id" varchar null, "department_id" integer not null, "code" varchar not null, "name" varchar not null, "description" text null, "retention_years" integer null, "is_active" tinyint(1) not null default '1', "created_at" datetime null, "updated_at" datetime null, foreign key("department_id") references "departments"("id") on delete cascade);

CREATE TABLE "master_archives" ("id" integer not null primary key autoincrement, "department_id" integer not null, "sub_department_id" integer null, "code" varchar null, "name" varchar not null, "document_type" varchar null, "retention_years" integer null default '5', "description" text null, "is_active" tinyint(1) not null default '1', "created_at" datetime null, "updated_at" datetime null, foreign key("department_id") references "departments"("id") on delete cascade, foreign key("sub_department_id") references "sub_departments"("id") on delete set null);

CREATE TABLE "warehouses" ("id" integer not null primary key autoincrement, "code" varchar not null, "name" varchar not null, "address" text null, "created_at" datetime null, "updated_at" datetime null, "is_fat_locked" tinyint(1) not null default '0', "is_active" tinyint(1) not null default '1');

CREATE TABLE "warehouse_locations" ("id" integer not null primary key autoincrement, "warehouse_id" integer not null, "rack_code" varchar not null, "shelf_code" varchar not null, "box_capacity" integer not null default '20', "current_box_count" integer not null default '0', "created_at" datetime null, "updated_at" datetime null, "room_sector" varchar null, "location_type" varchar not null default 'rack', "canvas_x" integer not null default '0', "canvas_y" integer not null default '0', "canvas_width" integer not null default '60', "canvas_height" integer not null default '120', "orientation" varchar not null default 'vertical', "rotation_angle" integer not null default '0', "is_locked" tinyint(1) not null default '1', "custom_color" varchar null, "assigned_department_id" integer null, "is_booked" tinyint(1) not null default '0', "booked_by_user_id" integer null, "booking_notes" text null, "is_fat_locked" tinyint(1) not null default '0', "total_sap" integer not null default '5', "boxes_per_sap" integer not null default '20', "box_type" varchar not null default 'TB 30g', "is_active" tinyint(1) not null default '1', "assigned_department_ids" text null, foreign key("warehouse_id") references "warehouses"("id") on delete cascade);

CREATE TABLE "numbering_formats" ("id" integer not null primary key autoincrement, "name" varchar not null, "pattern" varchar not null, "current_counter" integer not null default '0', "padding" integer not null default '4', "is_active" tinyint(1) not null default '1', "created_at" datetime null, "updated_at" datetime null);

CREATE TABLE "archives" (
                "id" integer not null primary key autoincrement,
                "box_number" varchar null,
                "department_id" integer not null,
                "created_by_user_id" integer not null,
                "title" varchar not null,
                "period_start_date" date not null,
                "period_end_date" date not null,
                "period_text" varchar null,
                "content_description" text not null,
                "retention_years" integer not null default '5',
                "retention_expiry_date" date null,
                "physical_condition" varchar not null default 'Baik',
                "file_path" varchar null,
                "warehouse_location_id" integer null,
                "status" varchar check ("status" in ('draft', 'pending_verification', 'approved_booked', 'in_warehouse', 'borrowed', 'taken', 'pending_destruction', 'pending_extension', 'destroyed')) not null default 'draft',
                "rejection_note" text null,
                "created_at" datetime null,
                "updated_at" datetime null,
                "company_name" varchar null,
                "document_type" varchar null,
                "period_yy_mm" varchar null,
                "scan_input_form" varchar null,
                "scan_approval_input" varchar null,
                "extension_reason" text null,
                "scan_extension_form" varchar null,
                "sub_department_id" integer null,
                "periode_doc" varchar null,
                "tgl_penyerahan" date null,
                "is_custom_doc_name" tinyint(1) not null default '0',
                "custom_doc_name" varchar null,
                "masa_simpan_custom" integer null,
                "warehouse_rack_slot_id" integer null,
                "periode" varchar(100) null,
                "origin_borrowing_log_id" integer null,
                "re_store_snapshot" text null,
                foreign key("department_id") references "departments"("id") on delete cascade,
                foreign key("created_by_user_id") references "users"("id") on delete cascade,
                foreign key("warehouse_location_id") references "warehouse_locations"("id") on delete set null
            );

CREATE TABLE "archive_items" ("id" integer not null primary key autoincrement, "archive_id" integer not null, "item_number" integer not null default '1', "document_name" varchar not null, "period_start" varchar null, "period_end" varchar null, "period_text" varchar null, "notes" text null, "created_at" datetime null, "updated_at" datetime null, foreign key("archive_id") references "archives"("id") on delete cascade);

CREATE TABLE "warehouse_rack_slots" ("id" integer not null primary key autoincrement, "warehouse_location_id" integer not null, "sap_level" integer not null, "layer" varchar not null, "slot_number" integer not null, "slot_code" varchar not null, "archive_id" integer null, "status" varchar not null default 'empty', "notes" text null, "created_at" datetime null, "updated_at" datetime null, "is_active" tinyint(1) not null default '1', foreign key("warehouse_location_id") references "warehouse_locations"("id") on delete cascade, foreign key("archive_id") references "archives"("id") on delete set null);

CREATE TABLE "warehouse_entry_logs" ("id" integer not null primary key autoincrement, "archive_id" integer not null, "pic_gudang_id" integer not null, "location_id" integer not null, "entry_date" datetime not null, "notes" text null, "created_at" datetime null, "updated_at" datetime null, foreign key("archive_id") references "archives"("id") on delete cascade, foreign key("pic_gudang_id") references "users"("id") on delete cascade, foreign key("location_id") references "warehouse_locations"("id") on delete cascade);

CREATE TABLE "borrowing_logs" (
                "id" integer not null primary key autoincrement,
                "archive_id" integer not null,
                "borrower_user_id" integer not null,
                "pic_gudang_id" integer null,
                "request_date" datetime not null,
                "borrow_date" datetime null,
                "expected_return_date" date null,
                "actual_return_date" datetime null,
                "purpose" text not null,
                "status" varchar not null default 'requested',
                "notes" text null,
                "created_at" datetime null,
                "updated_at" datetime null,
                "department_approval_by" integer null,
                "department_approved_at" datetime null,
                "scan_approval_borrow" varchar null,
                "approval_file" varchar null,
                "is_approval_uploaded" tinyint(1) not null default '0',
                "approval_status" varchar not null default 'pending',
                "approval_notes" text null, "snapshot_box_number" varchar null, "snapshot_title" varchar null, "snapshot_department" varchar null, "snapshot_items" text null, "active_restore_archive_id" integer null,
                foreign key("archive_id") references "archives"("id") on delete cascade,
                foreign key("borrower_user_id") references "users"("id") on delete cascade,
                foreign key("pic_gudang_id") references "users"("id") on delete set null
            );

CREATE TABLE "destruction_logs" ("id" integer not null primary key autoincrement, "archive_id" integer not null, "proposed_by_user_id" integer not null, "approved_by_dept_pic_id" integer null, "bap_number" varchar not null, "destruction_date" date not null, "method" varchar not null default 'Pencacahan / Shading Standard', "certificate_file" varchar null, "notes" text null, "created_at" datetime null, "updated_at" datetime null, "department_approval_by" integer null, "department_approved_at" datetime null, "scan_approval_destruction" varchar null, "extension_reason" text null, "scan_extension_form" varchar null, "approval_file" varchar null, "is_approval_uploaded" tinyint(1) not null default '0', "approval_status" varchar not null default 'pending', "approval_notes" text null, foreign key("archive_id") references "archives"("id") on delete cascade, foreign key("proposed_by_user_id") references "users"("id") on delete cascade, foreign key("approved_by_dept_pic_id") references "users"("id") on delete set null);

CREATE TABLE "archive_extension_logs" ("id" integer not null primary key autoincrement, "archive_id" integer not null, "proposed_by_user_id" integer not null, "additional_months" integer not null default '6', "additional_years" numeric null, "previous_expiry_date" date null, "new_expiry_date" date null, "extension_reason" text not null, "scan_extension_form" varchar null, "approval_status" varchar not null default 'pending', "approved_by_user_id" integer null, "approved_at" datetime null, "rejection_note" text null, "created_at" datetime null, "updated_at" datetime null, foreign key("archive_id") references "archives"("id") on delete cascade, foreign key("proposed_by_user_id") references "users"("id") on delete cascade, foreign key("approved_by_user_id") references "users"("id") on delete set null);

CREATE TABLE "activity_logs" ("id" integer not null primary key autoincrement, "user_id" integer null, "user_name" varchar null, "user_role" varchar null, "department_id" integer null, "action" varchar not null, "module" varchar not null, "description" text not null, "reference_id" varchar null, "ip_address" varchar null, "user_agent" text null, "properties" text null, "created_at" datetime null, "updated_at" datetime null, foreign key("user_id") references "users"("id") on delete set null, foreign key("department_id") references "departments"("id") on delete set null);

CREATE TABLE "app_settings" ("id" integer not null primary key autoincrement, "key" varchar not null, "value" text null, "group" varchar not null default 'general', "type" varchar not null default 'string', "description" varchar null, "created_at" datetime null, "updated_at" datetime null);

CREATE TABLE "system_events" ("id" integer not null primary key autoincrement, "event_type" varchar not null, "entity_type" varchar null, "entity_id" integer null, "department_id" integer null, "payload" text null, "created_at" datetime default CURRENT_TIMESTAMP not null);

CREATE INDEX "idx_locations_warehouse_id" on "warehouse_locations" ("warehouse_id");

CREATE INDEX "idx_archives_status" ON "archives" ("status");

CREATE INDEX "idx_archives_retention_expiry" ON "archives" ("retention_expiry_date");

CREATE INDEX "idx_archives_dept_status" ON "archives" ("department_id", "status");

CREATE INDEX "idx_archives_periode_doc" ON "archives" ("periode_doc");

CREATE INDEX "idx_archives_box_number" ON "archives" ("box_number");

CREATE INDEX "idx_archives_created_by" ON "archives" ("created_by_user_id");

CREATE INDEX "idx_archives_location" ON "archives" ("warehouse_location_id");

CREATE INDEX "idx_archives_rack_slot" ON "archives" ("warehouse_rack_slot_id");

CREATE INDEX "idx_archives_sub_dept" ON "archives" ("sub_department_id");

CREATE UNIQUE INDEX idx_archives_box_number_active ON archives (box_number) WHERE box_number IS NOT NULL AND box_number != '' AND status NOT IN ('taken', 'destroyed', 'borrowed');

CREATE INDEX "idx_archive_items_archive_id" ON "archive_items" ("archive_id");

CREATE INDEX "idx_rack_slots_archive_id" on "warehouse_rack_slots" ("archive_id");

CREATE INDEX "idx_rack_slots_location_status" on "warehouse_rack_slots" ("warehouse_location_id", "status");

CREATE INDEX "idx_rack_slots_slot_code" on "warehouse_rack_slots" ("slot_code");

CREATE INDEX "idx_borrowing_status" ON "borrowing_logs" ("status");

CREATE INDEX "idx_borrowing_archive_status" ON "borrowing_logs" ("archive_id", "status");

CREATE INDEX "idx_borrowing_borrower" ON "borrowing_logs" ("borrower_user_id");

CREATE INDEX "idx_borrowing_request_date" ON "borrowing_logs" ("request_date");

CREATE INDEX "idx_destruction_archive_id" ON "destruction_logs" ("archive_id");

CREATE INDEX "idx_destruction_approval_status" ON "destruction_logs" ("approval_status");

CREATE INDEX "idx_ext_logs_archive_id" on "archive_extension_logs" ("archive_id");

CREATE INDEX "idx_ext_logs_status" on "archive_extension_logs" ("approval_status");

CREATE INDEX "idx_ext_logs_proposed_by" on "archive_extension_logs" ("proposed_by_user_id");

CREATE INDEX "idx_ext_logs_created_at" on "archive_extension_logs" ("created_at");

CREATE INDEX "idx_activity_action" ON "activity_logs" ("action");

CREATE INDEX "idx_activity_user_id" ON "activity_logs" ("user_id");

CREATE INDEX "idx_activity_department_id" ON "activity_logs" ("department_id");

CREATE INDEX "idx_activity_created_at" ON "activity_logs" ("created_at");

CREATE INDEX "idx_events_seq_dept" on "system_events" ("id", "department_id");

CREATE INDEX "idx_events_created_at" on "system_events" ("created_at");
SQL);
    }

    /**
     * No rollback: dropping the baseline would delete every archive record.
     */
    public function down(): void
    {
        //
    }
};
