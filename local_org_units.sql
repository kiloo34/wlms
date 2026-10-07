--
-- PostgreSQL database dump
--

\restrict wDwnurCk8CrQl3mUJrhNFtf3y25tpaTbEOX9brhq12kbWj98WTs9tsPRPkObPO4

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
-- Name: org_units; Type: TABLE; Schema: public; Owner: robileksono
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


ALTER TABLE public.org_units OWNER TO robileksono;

--
-- Name: org_units org_units_code_unique; Type: CONSTRAINT; Schema: public; Owner: robileksono
--

ALTER TABLE ONLY public.org_units
    ADD CONSTRAINT org_units_code_unique UNIQUE (code);


--
-- Name: org_units org_units_pkey; Type: CONSTRAINT; Schema: public; Owner: robileksono
--

ALTER TABLE ONLY public.org_units
    ADD CONSTRAINT org_units_pkey PRIMARY KEY (id);


--
-- Name: org_unit_active_level_idx; Type: INDEX; Schema: public; Owner: robileksono
--

CREATE INDEX org_unit_active_level_idx ON public.org_units USING btree (is_active, org_level_id);


--
-- Name: org_units_org_level_id_index; Type: INDEX; Schema: public; Owner: robileksono
--

CREATE INDEX org_units_org_level_id_index ON public.org_units USING btree (org_level_id);


--
-- Name: org_units_parent_id_index; Type: INDEX; Schema: public; Owner: robileksono
--

CREATE INDEX org_units_parent_id_index ON public.org_units USING btree (parent_id);


--
-- Name: org_units org_units_org_level_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: robileksono
--

ALTER TABLE ONLY public.org_units
    ADD CONSTRAINT org_units_org_level_id_foreign FOREIGN KEY (org_level_id) REFERENCES public.org_levels(id) ON DELETE RESTRICT;


--
-- Name: org_units org_units_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: robileksono
--

ALTER TABLE ONLY public.org_units
    ADD CONSTRAINT org_units_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.org_units(id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict wDwnurCk8CrQl3mUJrhNFtf3y25tpaTbEOX9brhq12kbWj98WTs9tsPRPkObPO4

