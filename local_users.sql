--
-- PostgreSQL database dump
--

\restrict 6pxWGvxhf1mkxRSom0UsZUmeeuWrWpCTqQcPR5e2glsRB4M9v5cpxKrPqMZL8jX

-- Dumped from database version 18.6 (Homebrew)
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
-- Name: users; Type: TABLE; Schema: public; Owner: robileksono
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    uuid uuid NOT NULL,
    org_unit_id uuid,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'ACTIVE'::character varying NOT NULL,
    employee_id character varying(50),
    phone character varying(20),
    avatar_url character varying(255),
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    two_factor_secret text,
    two_factor_recovery_codes text,
    two_factor_confirmed_at timestamp(0) without time zone,
    current_team_id bigint,
    locale character varying(255) DEFAULT 'en'::character varying NOT NULL,
    CONSTRAINT users_status_check CHECK (((status)::text = ANY ((ARRAY['ACTIVE'::character varying, 'INACTIVE'::character varying, 'SUSPENDED'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO robileksono;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: robileksono
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO robileksono;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: robileksono
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: robileksono
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: robileksono
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_employee_id_unique; Type: CONSTRAINT; Schema: public; Owner: robileksono
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_employee_id_unique UNIQUE (employee_id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: robileksono
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: robileksono
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_uuid_unique UNIQUE (uuid);


--
-- Name: user_org_unit_idx; Type: INDEX; Schema: public; Owner: robileksono
--

CREATE INDEX user_org_unit_idx ON public.users USING btree (org_unit_id);


--
-- Name: user_status_idx; Type: INDEX; Schema: public; Owner: robileksono
--

CREATE INDEX user_status_idx ON public.users USING btree (status);


--
-- Name: users users_current_team_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: robileksono
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_current_team_id_foreign FOREIGN KEY (current_team_id) REFERENCES public.teams(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict 6pxWGvxhf1mkxRSom0UsZUmeeuWrWpCTqQcPR5e2glsRB4M9v5cpxKrPqMZL8jX

