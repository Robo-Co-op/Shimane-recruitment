-- =============================================================================
-- Shimane IB Recruitment — Supabase Schema
-- Run this once in: Supabase Dashboard → SQL Editor
--
-- Uses a dedicated 'shimaai' schema so data is isolated from other sites
-- (co-oplab, RoboUni, etc.) sharing the same Supabase project.
-- =============================================================================

-- Create isolated schema
CREATE SCHEMA IF NOT EXISTS shimaai;
SET search_path TO shimaai;

-- ── Admin users ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS shimaai.admin_users (
    id            BIGSERIAL PRIMARY KEY,
    email         TEXT UNIQUE NOT NULL,
    name          TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'viewer',
    created_at    TIMESTAMPTZ DEFAULT NOW(),
    last_login    TIMESTAMPTZ
);

-- ── Form drafts (save & resume) ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS shimaai.form_drafts (
    id               BIGSERIAL PRIMARY KEY,
    token            TEXT UNIQUE NOT NULL,
    email            TEXT,
    name             TEXT,
    lang             TEXT DEFAULT 'en',
    step_reached     INTEGER DEFAULT 1,
    form_data        TEXT DEFAULT '{}',
    created_at       TIMESTAMPTZ DEFAULT NOW(),
    updated_at       TIMESTAMPTZ DEFAULT NOW(),
    completed        INTEGER DEFAULT 0,
    reminder_sent_at TIMESTAMPTZ,
    reminder_count   INTEGER DEFAULT 0,
    ip_address       TEXT
);

-- ── Submitted applications ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS shimaai.form_submissions (
    id                   BIGSERIAL PRIMARY KEY,
    draft_id             BIGINT,
    name                 TEXT,
    email                TEXT,
    phone                TEXT,
    how_heard            TEXT,
    how_heard_other      TEXT,
    resume_url           TEXT,
    pc_skill             TEXT,
    ai_experience        TEXT,
    reason               TEXT,
    interview_day        TEXT,
    interview_day_other  TEXT,
    interview_time       TEXT,
    interview_time_other TEXT,
    support_program      TEXT,
    support_situation    TEXT,
    other_questions      TEXT,
    confirm_submit       TEXT,
    lang                 TEXT DEFAULT 'en',
    submitted_at         TIMESTAMPTZ DEFAULT NOW(),
    ip_address           TEXT,
    notes                TEXT,
    status               TEXT DEFAULT 'new'
);

-- ── Analytics events ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS shimaai.analytics_events (
    id         BIGSERIAL PRIMARY KEY,
    session_id TEXT,
    event_type TEXT,
    page       TEXT,
    lang       TEXT,
    referrer   TEXT,
    user_agent TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Index for fast date-range queries
CREATE INDEX IF NOT EXISTS idx_analytics_created_at
    ON shimaai.analytics_events (created_at DESC);
CREATE INDEX IF NOT EXISTS idx_analytics_event_type
    ON shimaai.analytics_events (event_type, created_at DESC);

-- ── Site content overrides ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS shimaai.site_content (
    id          BIGSERIAL PRIMARY KEY,
    content_key TEXT NOT NULL,
    lang        TEXT NOT NULL DEFAULT 'en',
    value       TEXT,
    updated_at  TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE (content_key, lang)
);

-- ── Forms (multi-form support) ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS shimaai.forms (
    id          BIGSERIAL PRIMARY KEY,
    slug        TEXT UNIQUE NOT NULL,
    lang        TEXT NOT NULL DEFAULT 'en',
    title       TEXT NOT NULL,
    description TEXT DEFAULT '',
    status      TEXT DEFAULT 'active',
    created_at  TIMESTAMPTZ DEFAULT NOW(),
    updated_at  TIMESTAMPTZ DEFAULT NOW()
);

-- ── Form questions ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS shimaai.form_questions (
    id           BIGSERIAL PRIMARY KEY,
    form_id      BIGINT NOT NULL REFERENCES shimaai.forms(id) ON DELETE CASCADE,
    sort_order   INTEGER DEFAULT 0,
    step         INTEGER DEFAULT 1,
    field_name   TEXT NOT NULL,
    field_type   TEXT NOT NULL DEFAULT 'text',
    label        TEXT NOT NULL DEFAULT '',
    hint         TEXT DEFAULT '',
    placeholder  TEXT DEFAULT '',
    required     INTEGER DEFAULT 0,
    options_json TEXT DEFAULT '[]',
    max_length   INTEGER,
    active       INTEGER DEFAULT 1,
    created_at   TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_form_questions_form_id
    ON shimaai.form_questions (form_id, sort_order);
