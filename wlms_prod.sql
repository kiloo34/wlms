--
-- PostgreSQL database dump
--

\restrict jVhadhF6ehILrPh1pDUtmPa7BvB1jhQPzpIVg2FKiQaHkvLjukEwOjGBidpyDXy

-- Dumped from database version 18.6 (4e955f5)
-- Dumped by pg_dump version 18.6 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.audit_logs (
    id uuid NOT NULL,
    actor_id bigint,
    auditable_type character varying(100) NOT NULL,
    auditable_id uuid,
    event character varying(50) NOT NULL,
    old_values json,
    new_values json,
    ip_address character varying(45),
    user_agent text,
    url character varying(500),
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.audit_logs OWNER TO laravel;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache OWNER TO laravel;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO laravel;

--
-- Name: comments; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.comments (
    id uuid NOT NULL,
    issue_id uuid NOT NULL,
    parent_id uuid,
    author_id bigint NOT NULL,
    body text NOT NULL,
    is_edited boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.comments OWNER TO laravel;

--
-- Name: doc_categories; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.doc_categories (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    "order" integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.doc_categories OWNER TO laravel;

--
-- Name: doc_pages; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.doc_pages (
    id uuid NOT NULL,
    category_id uuid NOT NULL,
    title character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    content text NOT NULL,
    "order" integer DEFAULT 0 NOT NULL,
    is_published boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.doc_pages OWNER TO laravel;

--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO laravel;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO laravel;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: issue_comments; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.issue_comments (
    id uuid NOT NULL,
    issue_id uuid NOT NULL,
    author_id bigint NOT NULL,
    body text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.issue_comments OWNER TO laravel;

--
-- Name: issue_histories; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.issue_histories (
    id uuid NOT NULL,
    issue_id uuid NOT NULL,
    actor_id bigint NOT NULL,
    field_changed character varying(50) NOT NULL,
    old_value text,
    new_value text,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.issue_histories OWNER TO laravel;

--
-- Name: issue_link_types; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.issue_link_types (
    id uuid NOT NULL,
    name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL,
    inbound_name character varying(50),
    inbound_slug character varying(50),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.issue_link_types OWNER TO laravel;

--
-- Name: issue_links; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.issue_links (
    id uuid NOT NULL,
    source_issue_id uuid NOT NULL,
    target_issue_id uuid NOT NULL,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    link_type_id uuid NOT NULL
);


ALTER TABLE public.issue_links OWNER TO laravel;

--
-- Name: issue_types; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.issue_types (
    id uuid NOT NULL,
    name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL,
    icon character varying(50),
    color character varying(20),
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.issue_types OWNER TO laravel;

--
-- Name: issues; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.issues (
    id uuid NOT NULL,
    project_id uuid NOT NULL,
    sprint_id uuid,
    status_id uuid NOT NULL,
    number bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    issue_type_id uuid NOT NULL,
    priority_id uuid NOT NULL,
    custom_fields json,
    story_points smallint,
    reporter_id bigint NOT NULL,
    assignee_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    original_estimate_seconds integer,
    remaining_estimate_seconds integer
);


ALTER TABLE public.issues OWNER TO laravel;

--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO laravel;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO laravel;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO laravel;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: menus; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.menus (
    id uuid NOT NULL,
    parent_id uuid,
    label character varying(100) NOT NULL,
    key character varying(100) NOT NULL,
    route character varying(200),
    icon character varying(50),
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.menus OWNER TO laravel;

--
-- Name: migrations; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO laravel;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.migrations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO laravel;

--
-- Name: migrations_id_seq1; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.migrations_id_seq1
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq1 OWNER TO laravel;

--
-- Name: migrations_id_seq1; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.migrations_id_seq1 OWNED BY public.migrations.id;


--
-- Name: notification_channels; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.notification_channels (
    id uuid NOT NULL,
    name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL,
    driver_class character varying(255) NOT NULL,
    icon character varying(50),
    is_active boolean DEFAULT true NOT NULL,
    is_user_configurable boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notification_channels OWNER TO laravel;

--
-- Name: notifications; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    user_id bigint NOT NULL,
    type character varying(100) NOT NULL,
    data json NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.notifications OWNER TO laravel;

--
-- Name: org_levels; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.org_levels (
    id uuid NOT NULL,
    name character varying(100) NOT NULL,
    slug character varying(50) NOT NULL,
    depth smallint NOT NULL,
    is_leaf boolean DEFAULT false NOT NULL,
    can_own_workspace boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.org_levels OWNER TO laravel;

--
-- Name: org_unit_closures; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.org_unit_closures (
    ancestor_id uuid NOT NULL,
    descendant_id uuid NOT NULL,
    depth smallint NOT NULL
);


ALTER TABLE public.org_unit_closures OWNER TO laravel;

--
-- Name: org_units; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.org_units (
    id uuid NOT NULL,
    parent_id uuid,
    org_level_id uuid NOT NULL,
    name character varying(150) NOT NULL,
    code character varying(30),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.org_units OWNER TO laravel;

--
-- Name: passkeys; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.passkeys (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    credential_id character varying(255) NOT NULL,
    credential json NOT NULL,
    last_used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.passkeys OWNER TO laravel;

--
-- Name: passkeys_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.passkeys_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.passkeys_id_seq OWNER TO laravel;

--
-- Name: passkeys_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.passkeys_id_seq OWNED BY public.passkeys.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO laravel;

--
-- Name: permissions; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.permissions (
    id uuid NOT NULL,
    name character varying(100) NOT NULL,
    description character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.permissions OWNER TO laravel;

--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO laravel;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.personal_access_tokens_id_seq OWNER TO laravel;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: priorities; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.priorities (
    id uuid NOT NULL,
    name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL,
    level smallint NOT NULL,
    icon character varying(50),
    color character varying(20),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.priorities OWNER TO laravel;

--
-- Name: projects; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.projects (
    id uuid NOT NULL,
    workspace_id uuid NOT NULL,
    workflow_id uuid,
    key character varying(10) NOT NULL,
    name character varying(150) NOT NULL,
    description text,
    status character varying(20) DEFAULT 'ACTIVE'::character varying NOT NULL,
    lead_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    priority_id uuid
);


ALTER TABLE public.projects OWNER TO laravel;

--
-- Name: role_menus; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.role_menus (
    role_id uuid NOT NULL,
    menu_id uuid NOT NULL
);


ALTER TABLE public.role_menus OWNER TO laravel;

--
-- Name: role_permissions; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.role_permissions (
    role_id uuid NOT NULL,
    permission_id uuid NOT NULL
);


ALTER TABLE public.role_permissions OWNER TO laravel;

--
-- Name: roles; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.roles (
    id uuid NOT NULL,
    name character varying(50) NOT NULL,
    scope character varying(30) DEFAULT 'GLOBAL'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.roles OWNER TO laravel;

--
-- Name: sessions; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO laravel;

--
-- Name: settings; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.settings (
    key character varying(255) NOT NULL,
    value json NOT NULL,
    type character varying(255) DEFAULT 'string'::character varying NOT NULL,
    "group" character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.settings OWNER TO laravel;

--
-- Name: sprint_metrics; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.sprint_metrics (
    id uuid NOT NULL,
    sprint_id uuid NOT NULL,
    planned_points integer DEFAULT 0 NOT NULL,
    completed_points integer DEFAULT 0 NOT NULL,
    total_issues smallint DEFAULT '0'::smallint NOT NULL,
    completed_issues smallint DEFAULT '0'::smallint NOT NULL,
    completion_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    snapshot_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.sprint_metrics OWNER TO laravel;

--
-- Name: sprints; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.sprints (
    id uuid NOT NULL,
    project_id uuid NOT NULL,
    name character varying(100) NOT NULL,
    goal text,
    state character varying(20) DEFAULT 'PENDING'::character varying NOT NULL,
    start_date timestamp(0) without time zone,
    end_date timestamp(0) without time zone,
    committed_points integer DEFAULT 0 NOT NULL,
    completed_points integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.sprints OWNER TO laravel;

--
-- Name: statuses; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.statuses (
    id uuid NOT NULL,
    name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL,
    category character varying(20) DEFAULT 'TODO'::character varying NOT NULL,
    color character varying(20),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.statuses OWNER TO laravel;

--
-- Name: team_invitations; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.team_invitations (
    id bigint NOT NULL,
    code character varying(64) NOT NULL,
    team_id bigint NOT NULL,
    email character varying(255) NOT NULL,
    role character varying(255) NOT NULL,
    invited_by bigint NOT NULL,
    expires_at timestamp(0) without time zone,
    accepted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.team_invitations OWNER TO laravel;

--
-- Name: team_invitations_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.team_invitations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.team_invitations_id_seq OWNER TO laravel;

--
-- Name: team_invitations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.team_invitations_id_seq OWNED BY public.team_invitations.id;


--
-- Name: team_members; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.team_members (
    id bigint NOT NULL,
    team_id bigint NOT NULL,
    user_id bigint NOT NULL,
    role character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.team_members OWNER TO laravel;

--
-- Name: team_members_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.team_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.team_members_id_seq OWNER TO laravel;

--
-- Name: team_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.team_members_id_seq OWNED BY public.team_members.id;


--
-- Name: teams; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.teams (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    is_personal boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.teams OWNER TO laravel;

--
-- Name: teams_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.teams_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.teams_id_seq OWNER TO laravel;

--
-- Name: teams_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.teams_id_seq OWNED BY public.teams.id;


--
-- Name: user_access_logs; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.user_access_logs (
    id bigint NOT NULL,
    user_id bigint,
    method character varying(10) NOT NULL,
    url character varying(500) NOT NULL,
    route_name character varying(150),
    response_code smallint,
    duration_ms integer,
    ip_address character varying(45),
    user_agent text,
    referer character varying(500),
    session_id character varying(100),
    accessed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.user_access_logs OWNER TO laravel;

--
-- Name: user_access_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.user_access_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_access_logs_id_seq OWNER TO laravel;

--
-- Name: user_access_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.user_access_logs_id_seq OWNED BY public.user_access_logs.id;


--
-- Name: user_roles; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.user_roles (
    id uuid NOT NULL,
    user_id bigint NOT NULL,
    role_id uuid NOT NULL,
    context_type character varying(50),
    context_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_roles OWNER TO laravel;

--
-- Name: users; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp(0) without time zone,
    current_team_id bigint,
    uuid uuid NOT NULL,
    org_unit_id uuid,
    status character varying(255) DEFAULT 'ACTIVE'::character varying NOT NULL,
    employee_id character varying(50),
    phone character varying(20),
    avatar_url character varying(255),
    locale character varying(255) DEFAULT 'en'::character varying NOT NULL,
    CONSTRAINT users_status_check CHECK (((status)::text = ANY ((ARRAY['ACTIVE'::character varying, 'INACTIVE'::character varying, 'SUSPENDED'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO laravel;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO laravel;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: workflow_transitions; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.workflow_transitions (
    id uuid NOT NULL,
    workflow_id uuid NOT NULL,
    from_status_id uuid,
    to_status_id uuid NOT NULL,
    name character varying(50) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.workflow_transitions OWNER TO laravel;

--
-- Name: workflows; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.workflows (
    id uuid NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    is_default boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


ALTER TABLE public.workflows OWNER TO laravel;

--
-- Name: worklogs; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.worklogs (
    id uuid NOT NULL,
    issue_id uuid NOT NULL,
    author_id bigint NOT NULL,
    time_spent_seconds integer NOT NULL,
    description text,
    started_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.worklogs OWNER TO laravel;

--
-- Name: workspace_members; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.workspace_members (
    id bigint NOT NULL,
    workspace_id uuid NOT NULL,
    user_id bigint NOT NULL,
    role character varying(255) DEFAULT 'viewer'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    daily_capacity_hours integer DEFAULT 8 NOT NULL
);


ALTER TABLE public.workspace_members OWNER TO laravel;

--
-- Name: workspace_members_id_seq; Type: SEQUENCE; Schema: public; Owner: laravel
--

CREATE SEQUENCE public.workspace_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.workspace_members_id_seq OWNER TO laravel;

--
-- Name: workspace_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: laravel
--

ALTER SEQUENCE public.workspace_members_id_seq OWNED BY public.workspace_members.id;


--
-- Name: workspaces; Type: TABLE; Schema: public; Owner: laravel
--

CREATE TABLE public.workspaces (
    id uuid NOT NULL,
    owner_group_id uuid NOT NULL,
    name character varying(150) NOT NULL,
    description text,
    status character varying(20) DEFAULT 'ACTIVE'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    settings json
);


ALTER TABLE public.workspaces OWNER TO laravel;

--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq1'::regclass);


--
-- Name: passkeys id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.passkeys ALTER COLUMN id SET DEFAULT nextval('public.passkeys_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: team_invitations id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.team_invitations ALTER COLUMN id SET DEFAULT nextval('public.team_invitations_id_seq'::regclass);


--
-- Name: team_members id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.team_members ALTER COLUMN id SET DEFAULT nextval('public.team_members_id_seq'::regclass);


--
-- Name: teams id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.teams ALTER COLUMN id SET DEFAULT nextval('public.teams_id_seq'::regclass);


--
-- Name: user_access_logs id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.user_access_logs ALTER COLUMN id SET DEFAULT nextval('public.user_access_logs_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: workspace_members id; Type: DEFAULT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workspace_members ALTER COLUMN id SET DEFAULT nextval('public.workspace_members_id_seq'::regclass);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: comments comments_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_pkey PRIMARY KEY (id);


--
-- Name: doc_categories doc_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.doc_categories
    ADD CONSTRAINT doc_categories_pkey PRIMARY KEY (id);


--
-- Name: doc_categories doc_categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.doc_categories
    ADD CONSTRAINT doc_categories_slug_unique UNIQUE (slug);


--
-- Name: doc_pages doc_pages_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.doc_pages
    ADD CONSTRAINT doc_pages_pkey PRIMARY KEY (id);


--
-- Name: doc_pages doc_pages_slug_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.doc_pages
    ADD CONSTRAINT doc_pages_slug_unique UNIQUE (slug);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: issue_comments issue_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_comments
    ADD CONSTRAINT issue_comments_pkey PRIMARY KEY (id);


--
-- Name: issue_histories issue_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_histories
    ADD CONSTRAINT issue_histories_pkey PRIMARY KEY (id);


--
-- Name: issue_link_types issue_link_types_inbound_slug_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_link_types
    ADD CONSTRAINT issue_link_types_inbound_slug_unique UNIQUE (inbound_slug);


--
-- Name: issue_link_types issue_link_types_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_link_types
    ADD CONSTRAINT issue_link_types_pkey PRIMARY KEY (id);


--
-- Name: issue_link_types issue_link_types_slug_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_link_types
    ADD CONSTRAINT issue_link_types_slug_unique UNIQUE (slug);


--
-- Name: issue_links issue_link_unique_new; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_links
    ADD CONSTRAINT issue_link_unique_new UNIQUE (source_issue_id, target_issue_id, link_type_id);


--
-- Name: issue_links issue_links_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_links
    ADD CONSTRAINT issue_links_pkey PRIMARY KEY (id);


--
-- Name: issues issue_project_number_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issue_project_number_unique UNIQUE (project_id, number);


--
-- Name: issue_types issue_types_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_types
    ADD CONSTRAINT issue_types_pkey PRIMARY KEY (id);


--
-- Name: issue_types issue_types_slug_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_types
    ADD CONSTRAINT issue_types_slug_unique UNIQUE (slug);


--
-- Name: issues issues_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: menus menus_key_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.menus
    ADD CONSTRAINT menus_key_unique UNIQUE (key);


--
-- Name: menus menus_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.menus
    ADD CONSTRAINT menus_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notification_channels notification_channels_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.notification_channels
    ADD CONSTRAINT notification_channels_pkey PRIMARY KEY (id);


--
-- Name: notification_channels notification_channels_slug_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.notification_channels
    ADD CONSTRAINT notification_channels_slug_unique UNIQUE (slug);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: org_levels org_levels_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.org_levels
    ADD CONSTRAINT org_levels_pkey PRIMARY KEY (id);


--
-- Name: org_levels org_levels_slug_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.org_levels
    ADD CONSTRAINT org_levels_slug_unique UNIQUE (slug);


--
-- Name: org_unit_closures org_unit_closures_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.org_unit_closures
    ADD CONSTRAINT org_unit_closures_pkey PRIMARY KEY (ancestor_id, descendant_id);


--
-- Name: org_units org_units_code_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.org_units
    ADD CONSTRAINT org_units_code_unique UNIQUE (code);


--
-- Name: org_units org_units_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.org_units
    ADD CONSTRAINT org_units_pkey PRIMARY KEY (id);


--
-- Name: passkeys passkeys_credential_id_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.passkeys
    ADD CONSTRAINT passkeys_credential_id_unique UNIQUE (credential_id);


--
-- Name: passkeys passkeys_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.passkeys
    ADD CONSTRAINT passkeys_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permissions permissions_name_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_unique UNIQUE (name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: priorities priorities_level_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.priorities
    ADD CONSTRAINT priorities_level_unique UNIQUE (level);


--
-- Name: priorities priorities_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.priorities
    ADD CONSTRAINT priorities_pkey PRIMARY KEY (id);


--
-- Name: priorities priorities_slug_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.priorities
    ADD CONSTRAINT priorities_slug_unique UNIQUE (slug);


--
-- Name: projects projects_key_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_key_unique UNIQUE (key);


--
-- Name: projects projects_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_pkey PRIMARY KEY (id);


--
-- Name: role_menus role_menus_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.role_menus
    ADD CONSTRAINT role_menus_pkey PRIMARY KEY (role_id, menu_id);


--
-- Name: role_permissions role_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_pkey PRIMARY KEY (role_id, permission_id);


--
-- Name: roles roles_name_scope_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_scope_unique UNIQUE (name, scope);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (key);


--
-- Name: sprint_metrics sprint_metrics_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.sprint_metrics
    ADD CONSTRAINT sprint_metrics_pkey PRIMARY KEY (id);


--
-- Name: sprint_metrics sprint_metrics_sprint_id_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.sprint_metrics
    ADD CONSTRAINT sprint_metrics_sprint_id_unique UNIQUE (sprint_id);


--
-- Name: sprints sprints_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.sprints
    ADD CONSTRAINT sprints_pkey PRIMARY KEY (id);


--
-- Name: statuses statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.statuses
    ADD CONSTRAINT statuses_pkey PRIMARY KEY (id);


--
-- Name: statuses statuses_slug_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.statuses
    ADD CONSTRAINT statuses_slug_unique UNIQUE (slug);


--
-- Name: team_invitations team_invitations_code_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.team_invitations
    ADD CONSTRAINT team_invitations_code_unique UNIQUE (code);


--
-- Name: team_invitations team_invitations_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.team_invitations
    ADD CONSTRAINT team_invitations_pkey PRIMARY KEY (id);


--
-- Name: team_members team_members_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.team_members
    ADD CONSTRAINT team_members_pkey PRIMARY KEY (id);


--
-- Name: team_members team_members_team_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.team_members
    ADD CONSTRAINT team_members_team_id_user_id_unique UNIQUE (team_id, user_id);


--
-- Name: teams teams_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.teams
    ADD CONSTRAINT teams_pkey PRIMARY KEY (id);


--
-- Name: teams teams_slug_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.teams
    ADD CONSTRAINT teams_slug_unique UNIQUE (slug);


--
-- Name: user_access_logs user_access_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.user_access_logs
    ADD CONSTRAINT user_access_logs_pkey PRIMARY KEY (id);


--
-- Name: user_roles user_role_context_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_role_context_unique UNIQUE (user_id, role_id, context_type, context_id);


--
-- Name: user_roles user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_employee_id_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_employee_id_unique UNIQUE (employee_id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_uuid_unique UNIQUE (uuid);


--
-- Name: workflow_transitions workflow_transition_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workflow_transitions
    ADD CONSTRAINT workflow_transition_unique UNIQUE (workflow_id, from_status_id, to_status_id);


--
-- Name: workflow_transitions workflow_transitions_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workflow_transitions
    ADD CONSTRAINT workflow_transitions_pkey PRIMARY KEY (id);


--
-- Name: workflows workflows_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workflows
    ADD CONSTRAINT workflows_pkey PRIMARY KEY (id);


--
-- Name: worklogs worklogs_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.worklogs
    ADD CONSTRAINT worklogs_pkey PRIMARY KEY (id);


--
-- Name: workspace_members workspace_members_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workspace_members
    ADD CONSTRAINT workspace_members_pkey PRIMARY KEY (id);


--
-- Name: workspace_members workspace_members_workspace_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workspace_members
    ADD CONSTRAINT workspace_members_workspace_id_user_id_unique UNIQUE (workspace_id, user_id);


--
-- Name: workspaces workspaces_pkey; Type: CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workspaces
    ADD CONSTRAINT workspaces_pkey PRIMARY KEY (id);


--
-- Name: access_ip_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX access_ip_idx ON public.user_access_logs USING btree (ip_address);


--
-- Name: access_response_time_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX access_response_time_idx ON public.user_access_logs USING btree (response_code, accessed_at);


--
-- Name: access_route_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX access_route_idx ON public.user_access_logs USING btree (route_name);


--
-- Name: access_user_time_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX access_user_time_idx ON public.user_access_logs USING btree (user_id, accessed_at);


--
-- Name: audit_actor_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX audit_actor_idx ON public.audit_logs USING btree (actor_id);


--
-- Name: audit_event_time_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX audit_event_time_idx ON public.audit_logs USING btree (event, created_at);


--
-- Name: audit_morphs_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX audit_morphs_idx ON public.audit_logs USING btree (auditable_type, auditable_id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: comment_issue_thread_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX comment_issue_thread_idx ON public.comments USING btree (issue_id, parent_id);


--
-- Name: comments_author_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX comments_author_id_index ON public.comments USING btree (author_id);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: idx_issue_comments_timeline; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX idx_issue_comments_timeline ON public.issue_comments USING btree (issue_id, created_at);


--
-- Name: issue_board_view_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_board_view_idx ON public.issues USING btree (project_id, sprint_id, status_id);


--
-- Name: issue_history_field_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_history_field_idx ON public.issue_histories USING btree (issue_id, field_changed);


--
-- Name: issue_history_timeline_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_history_timeline_idx ON public.issue_histories USING btree (issue_id, created_at);


--
-- Name: issue_link_type_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_link_type_idx ON public.issue_links USING btree (link_type_id);


--
-- Name: issue_link_types_slug_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_link_types_slug_index ON public.issue_link_types USING btree (slug);


--
-- Name: issue_links_source_issue_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_links_source_issue_id_index ON public.issue_links USING btree (source_issue_id);


--
-- Name: issue_links_target_issue_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_links_target_issue_id_index ON public.issue_links USING btree (target_issue_id);


--
-- Name: issue_priority_lookup_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_priority_lookup_idx ON public.issues USING btree (priority_id);


--
-- Name: issue_type_active_order_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_type_active_order_idx ON public.issue_types USING btree (is_active, sort_order);


--
-- Name: issue_type_lookup_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_type_lookup_idx ON public.issues USING btree (issue_type_id);


--
-- Name: issue_types_slug_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issue_types_slug_index ON public.issue_types USING btree (slug);


--
-- Name: issues_assignee_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issues_assignee_id_index ON public.issues USING btree (assignee_id);


--
-- Name: issues_reporter_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX issues_reporter_id_index ON public.issues USING btree (reporter_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: menu_active_order_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX menu_active_order_idx ON public.menus USING btree (is_active, sort_order);


--
-- Name: menus_parent_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX menus_parent_id_index ON public.menus USING btree (parent_id);


--
-- Name: notification_channels_slug_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX notification_channels_slug_index ON public.notification_channels USING btree (slug);


--
-- Name: notifications_user_id_read_at_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX notifications_user_id_read_at_index ON public.notifications USING btree (user_id, read_at);


--
-- Name: org_levels_depth_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX org_levels_depth_index ON public.org_levels USING btree (depth);


--
-- Name: org_levels_is_leaf_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX org_levels_is_leaf_index ON public.org_levels USING btree (is_leaf);


--
-- Name: org_levels_slug_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX org_levels_slug_index ON public.org_levels USING btree (slug);


--
-- Name: org_unit_active_level_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX org_unit_active_level_idx ON public.org_units USING btree (is_active, org_level_id);


--
-- Name: org_unit_closures_descendant_id_depth_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX org_unit_closures_descendant_id_depth_index ON public.org_unit_closures USING btree (descendant_id, depth);


--
-- Name: org_units_org_level_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX org_units_org_level_id_index ON public.org_units USING btree (org_level_id);


--
-- Name: org_units_parent_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX org_units_parent_id_index ON public.org_units USING btree (parent_id);


--
-- Name: passkeys_user_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX passkeys_user_id_index ON public.passkeys USING btree (user_id);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: priorities_slug_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX priorities_slug_index ON public.priorities USING btree (slug);


--
-- Name: projects_lead_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX projects_lead_id_index ON public.projects USING btree (lead_id);


--
-- Name: projects_priority_id_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX projects_priority_id_idx ON public.projects USING btree (priority_id);


--
-- Name: projects_status_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX projects_status_index ON public.projects USING btree (status);


--
-- Name: projects_workspace_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX projects_workspace_id_index ON public.projects USING btree (workspace_id);


--
-- Name: roles_scope_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX roles_scope_index ON public.roles USING btree (scope);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: settings_group_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX settings_group_index ON public.settings USING btree ("group");


--
-- Name: sprint_project_state_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX sprint_project_state_idx ON public.sprints USING btree (project_id, state);


--
-- Name: statuses_category_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX statuses_category_index ON public.statuses USING btree (category);


--
-- Name: user_context_lookup_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX user_context_lookup_idx ON public.user_roles USING btree (user_id, context_type, context_id);


--
-- Name: user_org_unit_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX user_org_unit_idx ON public.users USING btree (org_unit_id);


--
-- Name: user_status_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX user_status_idx ON public.users USING btree (status);


--
-- Name: workflow_transitions_workflow_id_index; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX workflow_transitions_workflow_id_index ON public.workflow_transitions USING btree (workflow_id);


--
-- Name: worklog_issue_author_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX worklog_issue_author_idx ON public.worklogs USING btree (issue_id, author_id);


--
-- Name: workspace_group_status_idx; Type: INDEX; Schema: public; Owner: laravel
--

CREATE INDEX workspace_group_status_idx ON public.workspaces USING btree (owner_group_id, status);


--
-- Name: comments comments_issue_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_issue_id_foreign FOREIGN KEY (issue_id) REFERENCES public.issues(id) ON DELETE CASCADE;


--
-- Name: comments comments_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.comments
    ADD CONSTRAINT comments_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.comments(id) ON DELETE CASCADE;


--
-- Name: doc_pages doc_pages_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.doc_pages
    ADD CONSTRAINT doc_pages_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.doc_categories(id) ON DELETE CASCADE;


--
-- Name: issue_comments issue_comments_issue_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_comments
    ADD CONSTRAINT issue_comments_issue_id_foreign FOREIGN KEY (issue_id) REFERENCES public.issues(id) ON DELETE CASCADE;


--
-- Name: issue_histories issue_histories_issue_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_histories
    ADD CONSTRAINT issue_histories_issue_id_foreign FOREIGN KEY (issue_id) REFERENCES public.issues(id) ON DELETE CASCADE;


--
-- Name: issue_links issue_links_link_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_links
    ADD CONSTRAINT issue_links_link_type_id_foreign FOREIGN KEY (link_type_id) REFERENCES public.issue_link_types(id) ON DELETE RESTRICT;


--
-- Name: issue_links issue_links_source_issue_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_links
    ADD CONSTRAINT issue_links_source_issue_id_foreign FOREIGN KEY (source_issue_id) REFERENCES public.issues(id) ON DELETE CASCADE;


--
-- Name: issue_links issue_links_target_issue_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issue_links
    ADD CONSTRAINT issue_links_target_issue_id_foreign FOREIGN KEY (target_issue_id) REFERENCES public.issues(id) ON DELETE CASCADE;


--
-- Name: issues issues_issue_type_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_issue_type_id_foreign FOREIGN KEY (issue_type_id) REFERENCES public.issue_types(id) ON DELETE RESTRICT;


--
-- Name: issues issues_priority_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_priority_id_foreign FOREIGN KEY (priority_id) REFERENCES public.priorities(id) ON DELETE RESTRICT;


--
-- Name: issues issues_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: issues issues_sprint_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_sprint_id_foreign FOREIGN KEY (sprint_id) REFERENCES public.sprints(id) ON DELETE SET NULL;


--
-- Name: issues issues_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.issues
    ADD CONSTRAINT issues_status_id_foreign FOREIGN KEY (status_id) REFERENCES public.statuses(id) ON DELETE RESTRICT;


--
-- Name: menus menus_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.menus
    ADD CONSTRAINT menus_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.menus(id) ON DELETE RESTRICT;


--
-- Name: notifications notifications_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: org_unit_closures org_unit_closures_ancestor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.org_unit_closures
    ADD CONSTRAINT org_unit_closures_ancestor_id_foreign FOREIGN KEY (ancestor_id) REFERENCES public.org_units(id) ON DELETE CASCADE;


--
-- Name: org_unit_closures org_unit_closures_descendant_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.org_unit_closures
    ADD CONSTRAINT org_unit_closures_descendant_id_foreign FOREIGN KEY (descendant_id) REFERENCES public.org_units(id) ON DELETE CASCADE;


--
-- Name: org_units org_units_org_level_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.org_units
    ADD CONSTRAINT org_units_org_level_id_foreign FOREIGN KEY (org_level_id) REFERENCES public.org_levels(id) ON DELETE RESTRICT;


--
-- Name: org_units org_units_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.org_units
    ADD CONSTRAINT org_units_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.org_units(id) ON DELETE RESTRICT;


--
-- Name: passkeys passkeys_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.passkeys
    ADD CONSTRAINT passkeys_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: projects projects_workflow_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_workflow_id_foreign FOREIGN KEY (workflow_id) REFERENCES public.workflows(id) ON DELETE SET NULL;


--
-- Name: projects projects_workspace_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.projects
    ADD CONSTRAINT projects_workspace_id_foreign FOREIGN KEY (workspace_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- Name: role_menus role_menus_menu_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.role_menus
    ADD CONSTRAINT role_menus_menu_id_foreign FOREIGN KEY (menu_id) REFERENCES public.menus(id) ON DELETE CASCADE;


--
-- Name: role_menus role_menus_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.role_menus
    ADD CONSTRAINT role_menus_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_permissions role_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.role_permissions
    ADD CONSTRAINT role_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: sprint_metrics sprint_metrics_sprint_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.sprint_metrics
    ADD CONSTRAINT sprint_metrics_sprint_id_foreign FOREIGN KEY (sprint_id) REFERENCES public.sprints(id) ON DELETE CASCADE;


--
-- Name: sprints sprints_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.sprints
    ADD CONSTRAINT sprints_project_id_foreign FOREIGN KEY (project_id) REFERENCES public.projects(id) ON DELETE CASCADE;


--
-- Name: team_invitations team_invitations_invited_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.team_invitations
    ADD CONSTRAINT team_invitations_invited_by_foreign FOREIGN KEY (invited_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: team_invitations team_invitations_team_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.team_invitations
    ADD CONSTRAINT team_invitations_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE CASCADE;


--
-- Name: team_members team_members_team_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.team_members
    ADD CONSTRAINT team_members_team_id_foreign FOREIGN KEY (team_id) REFERENCES public.teams(id) ON DELETE CASCADE;


--
-- Name: team_members team_members_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.team_members
    ADD CONSTRAINT team_members_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: user_roles user_roles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: users users_current_team_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_current_team_id_foreign FOREIGN KEY (current_team_id) REFERENCES public.teams(id) ON DELETE SET NULL;


--
-- Name: workflow_transitions workflow_transitions_from_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workflow_transitions
    ADD CONSTRAINT workflow_transitions_from_status_id_foreign FOREIGN KEY (from_status_id) REFERENCES public.statuses(id) ON DELETE CASCADE;


--
-- Name: workflow_transitions workflow_transitions_to_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workflow_transitions
    ADD CONSTRAINT workflow_transitions_to_status_id_foreign FOREIGN KEY (to_status_id) REFERENCES public.statuses(id) ON DELETE CASCADE;


--
-- Name: workflow_transitions workflow_transitions_workflow_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workflow_transitions
    ADD CONSTRAINT workflow_transitions_workflow_id_foreign FOREIGN KEY (workflow_id) REFERENCES public.workflows(id) ON DELETE CASCADE;


--
-- Name: worklogs worklogs_issue_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.worklogs
    ADD CONSTRAINT worklogs_issue_id_foreign FOREIGN KEY (issue_id) REFERENCES public.issues(id) ON DELETE CASCADE;


--
-- Name: workspace_members workspace_members_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workspace_members
    ADD CONSTRAINT workspace_members_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: workspace_members workspace_members_workspace_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: laravel
--

ALTER TABLE ONLY public.workspace_members
    ADD CONSTRAINT workspace_members_workspace_id_foreign FOREIGN KEY (workspace_id) REFERENCES public.workspaces(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict jVhadhF6ehILrPh1pDUtmPa7BvB1jhQPzpIVg2FKiQaHkvLjukEwOjGBidpyDXy

