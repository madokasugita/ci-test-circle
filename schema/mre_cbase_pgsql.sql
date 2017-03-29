--
-- PostgreSQL database dump
--

SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

--
-- Name: _seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE _seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public._seq OWNER TO www;

--
-- Name: _seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('_seq', 1, true);


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: auth_set; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE auth_set (
    evid integer,
    muid integer NOT NULL,
    page text,
    auth_set_id integer NOT NULL
);


ALTER TABLE public.auth_set OWNER TO www;

--
-- Name: COLUMN auth_set.evid; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN auth_set.evid IS '対象evid(nullですべて)';


--
-- Name: COLUMN auth_set.muid; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN auth_set.muid IS '権限所持者';


--
-- Name: COLUMN auth_set.page; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN auth_set.page IS '対象ページ';


--
-- Name: auth_set_auth_set_id_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE auth_set_auth_set_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.auth_set_auth_set_id_seq OWNER TO www;

--
-- Name: auth_set_auth_set_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: www
--

ALTER SEQUENCE auth_set_auth_set_id_seq OWNED BY auth_set.auth_set_id;


--
-- Name: auth_set_auth_set_id_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('auth_set_auth_set_id_seq', 543, true);


--
-- Name: auth_set_div; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE auth_set_div (
    muid integer,
    div1 text,
    div2 text,
    div3 text,
    asd_id integer NOT NULL
);


ALTER TABLE public.auth_set_div OWNER TO www;

--
-- Name: TABLE auth_set_div; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON TABLE auth_set_div IS '360度_所属別管理権限';


--
-- Name: auth_set_div_asd_id_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE auth_set_div_asd_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.auth_set_div_asd_id_seq OWNER TO www;

--
-- Name: auth_set_div_asd_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: www
--

ALTER SEQUENCE auth_set_div_asd_id_seq OWNED BY auth_set_div.asd_id;


--
-- Name: auth_set_div_asd_id_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('auth_set_div_asd_id_seq', 53, true);


SET default_with_oids = true;

--
-- Name: backup_data; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE backup_data (
    serial_no character(8),
    cdate timestamp without time zone,
    cacheid integer,
    rid character varying(8),
    bdid character(24),
    udate timestamp without time zone,
    evid integer,
    page smallint DEFAULT 1 NOT NULL,
    event_data_id integer,
    restore_id text,
    target text
);


ALTER TABLE public.backup_data OWNER TO www;

--
-- Name: backup_event; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE backup_event (
    cacheid integer NOT NULL,
    arrayserial text,
    rid character varying(8)
);


ALTER TABLE public.backup_event OWNER TO www;

--
-- Name: backup_event_cacheid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE backup_event_cacheid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.backup_event_cacheid_seq OWNER TO www;

--
-- Name: backup_event_cacheid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: www
--

ALTER SEQUENCE backup_event_cacheid_seq OWNED BY backup_event.cacheid;


--
-- Name: backup_event_cacheid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('backup_event_cacheid_seq', 1587, true);


SET default_with_oids = false;

--
-- Name: choice; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE choice (
    evid integer,
    seid integer,
    num smallint,
    choice text,
    div text
);


ALTER TABLE public.choice OWNER TO www;

--
-- Name: cnid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE cnid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.cnid_seq OWNER TO www;

--
-- Name: cnid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('cnid_seq', 242, true);


--
-- Name: cond; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE cond (
    cnid integer,
    name text,
    strsql text,
    pgcache text,
    flgt smallint,
    cdate timestamp without time zone,
    udate timestamp without time zone,
    muid integer
);


ALTER TABLE public.cond OWNER TO www;

--
-- Name: div; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE div (
    div1 text,
    div1_name text,
    div1_sort integer,
    div2 text,
    div2_name text,
    div2_sort integer,
    div3 text,
    div3_name text,
    div3_sort integer
);


ALTER TABLE public.div OWNER TO www;

--
-- Name: TABLE div; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON TABLE div IS '360度_所属マスタ';


--
-- Name: COLUMN div.div1; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN div.div1 IS '所属(大)コード';


--
-- Name: COLUMN div.div1_name; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN div.div1_name IS '所属(大)表示名';


--
-- Name: COLUMN div.div1_sort; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN div.div1_sort IS '所属(大)並び順';


--
-- Name: COLUMN div.div2; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN div.div2 IS '所属(中)コード';


--
-- Name: COLUMN div.div2_name; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN div.div2_name IS '所属(中)表示名';


--
-- Name: COLUMN div.div2_sort; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN div.div2_sort IS '所属(中)並び順';


--
-- Name: COLUMN div.div3; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN div.div3 IS '所属(小)コード';


--
-- Name: COLUMN div.div3_name; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN div.div3_name IS '所属(小)表示名';


--
-- Name: COLUMN div.div3_sort; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN div.div3_sort IS '所属(小)並び順';


--
-- Name: event; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE event (
    evid integer,
    rid character varying(8),
    name text,
    type integer,
    flgs integer,
    flgl integer,
    flgo integer,
    limitc integer,
    point integer,
    mfid integer,
    htmlh text,
    htmlm text,
    htmlf text,
    url text,
    setting text,
    sdate timestamp without time zone,
    edate timestamp without time zone,
    cdate timestamp without time zone,
    udate timestamp without time zone,
    muid integer,
    htmls text,
    lastpage integer,
    randomize text,
    mailaddress text,
    mailname text,
    id text,
    pw text,
    htmls2 text
);


ALTER TABLE public.event OWNER TO www;

--
-- Name: event_data; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE event_data (
    evid integer,
    serial_no character(8),
    cdate timestamp without time zone,
    flg character(1),
    answer_state smallint DEFAULT 0 NOT NULL,
    udate timestamp without time zone,
    event_data_id integer,
    target text,
    ucount smallint DEFAULT 0 NOT NULL
);


ALTER TABLE public.event_data OWNER TO www;

--
-- Name: COLUMN event_data.udate; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN event_data.udate IS 'このデータの最終更新時刻';


--
-- Name: COLUMN event_data.ucount; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN event_data.ucount IS '管理画面からコメントを書き換えた回数';


--
-- Name: event_data_event_data_id_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE event_data_event_data_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.event_data_event_data_id_seq OWNER TO www;

--
-- Name: event_data_event_data_id_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('event_data_event_data_id_seq', 1115, true);


--
-- Name: event_design; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE event_design (
    evid integer,
    sa_title text,
    sa_cheader text,
    sa_cbody text,
    sa_cother text,
    sa_cfooter text,
    ma_title text,
    ma_cheader text,
    ma_cbody text,
    ma_cother text,
    ma_cfooter text,
    fa_title text,
    fa_cheader text,
    fa_cbody text,
    fa_cfooter text,
    mx_title text,
    mx_cheader text,
    mx_cbody text,
    mx_cchoice text,
    mx_cfooter text
);


ALTER TABLE public.event_design OWNER TO www;

--
-- Name: evid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE evid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.evid_seq OWNER TO www;

--
-- Name: evid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('evid_seq', 200, true);


--
-- Name: inquiry; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE inquiry (
    inqid integer NOT NULL,
    uid text,
    category smallint,
    method smallint,
    title text,
    status smallint,
    muid integer,
    firstrsvdate timestamp without time zone,
    rsvdate timestamp without time zone,
    senddate timestamp without time zone
);


ALTER TABLE public.inquiry OWNER TO www;

--
-- Name: inquiry_inqid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE inquiry_inqid_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.inquiry_inqid_seq OWNER TO www;

--
-- Name: inquiry_inqid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: www
--

ALTER SEQUENCE inquiry_inqid_seq OWNED BY inquiry.inqid;


--
-- Name: inquiry_inqid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('inquiry_inqid_seq', 1, false);


--
-- Name: mail_format; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE mail_format (
    mfid integer,
    name text,
    title text,
    body text,
    header text,
    footer text,
    file text,
    cdate timestamp without time zone,
    udate timestamp without time zone,
    muid integer,
    title_1 text,
    body_1 text,
    title_2 text,
    body_2 text,
    title_3 text,
    body_3 text,
    title_4 text,
    body_4 text,
    mfodr integer,
    file_1 text,
    file_2 text,
    file_3 text,
    file_4 text
);


ALTER TABLE public.mail_format OWNER TO www;

--
-- Name: mail_log; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE mail_log (
    mrid integer,
    serial_no character(8) NOT NULL,
    result integer
);


ALTER TABLE public.mail_log OWNER TO www;

--
-- Name: COLUMN mail_log.result; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN mail_log.result IS '0=>失敗,1=>成功';


--
-- Name: mail_received; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE mail_received (
    mail_received_id integer NOT NULL,
    mail_to text,
    mail_from text,
    title text,
    body text,
    cdate timestamp with time zone,
    rdate timestamp with time zone,
    response_flag integer,
    response_status integer
);


ALTER TABLE public.mail_received OWNER TO www;

--
-- Name: TABLE mail_received; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON TABLE mail_received IS '360度_受信メール';


--
-- Name: COLUMN mail_received.rdate; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN mail_received.rdate IS '送信日時';


--
-- Name: COLUMN mail_received.response_flag; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN mail_received.response_flag IS '0->未返信 1->返信済';


--
-- Name: COLUMN mail_received.response_status; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN mail_received.response_status IS '10->未対応 20->対応済';


--
-- Name: mail_received_mail_received_id_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE mail_received_mail_received_id_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.mail_received_mail_received_id_seq OWNER TO www;

--
-- Name: mail_received_mail_received_id_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('mail_received_mail_received_id_seq', 24, true);


--
-- Name: mail_rsv; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE mail_rsv (
    mrid integer,
    name text,
    mfid integer,
    cnid integer,
    flgs integer,
    flgl integer,
    mdate timestamp without time zone,
    count integer,
    cdate timestamp without time zone,
    udate timestamp without time zone,
    muid integer,
    evid integer
);


ALTER TABLE public.mail_rsv OWNER TO www;

--
-- Name: COLUMN mail_rsv.evid; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN mail_rsv.evid IS 'evid';


--
-- Name: msgid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE msgid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.msgid_seq OWNER TO www;

--
-- Name: msgid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('msgid_seq', 567, true);


--
-- Name: message; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE message (
    msgid integer DEFAULT nextval('msgid_seq'::regclass) NOT NULL,
    key text,
    place1 text,
    place2 text,
    type text,
    name text,
    body_0 text,
    body_1 text,
    body_2 text,
    body_3 text,
    body_4 text,
    memo text
);


ALTER TABLE public.message OWNER TO www;

--
-- Name: mfid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE mfid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.mfid_seq OWNER TO www;

--
-- Name: mfid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('mfid_seq', 37, true);


--
-- Name: mrid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE mrid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.mrid_seq OWNER TO www;

--
-- Name: mrid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('mrid_seq', 209, true);


--
-- Name: muid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE muid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.muid_seq OWNER TO www;

--
-- Name: muid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('muid_seq', 77, true);


--
-- Name: musr; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE musr (
    muid integer,
    id text,
    pw text,
    div text,
    name text,
    flg smallint,
    permitted text,
    email text,
    permitted_column text
);


ALTER TABLE public.musr OWNER TO www;

--
-- Name: project_id_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE project_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.project_id_seq OWNER TO www;

--
-- Name: project_id_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('project_id_seq', 1, false);


--
-- Name: project; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE project (
    project_id integer DEFAULT nextval('project_id_seq'::regclass) NOT NULL,
    name text,
    mdate_1 date,
    mdate_2 date,
    cdate timestamp without time zone,
    udate timestamp without time zone
);


ALTER TABLE public.project OWNER TO www;

--
-- Name: seid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE seid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.seid_seq OWNER TO www;

--
-- Name: seid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('seid_seq', 1002378, true);


--
-- Name: subevent; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE subevent (
    seid integer,
    evid integer,
    title text,
    type1 integer,
    type2 character(1),
    choice text,
    hissu integer,
    width integer,
    rows integer,
    cond text,
    page integer,
    other integer,
    html1 text,
    html2 text,
    cond2 text,
    cond3 text,
    cond4 text,
    cond5 text,
    ext text,
    fel smallint,
    chtable text,
    matrix smallint,
    randomize text,
    cond360 text,
    num smallint DEFAULT 0 NOT NULL,
    category1 smallint DEFAULT 0 NOT NULL,
    category2 smallint DEFAULT 0 NOT NULL,
    num_ext smallint DEFAULT 0 NOT NULL
);


ALTER TABLE public.subevent OWNER TO www;

--
-- Name: COLUMN subevent.type1; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN subevent.type1 IS '1->SA 2->MA 4->フリー';


--
-- Name: COLUMN subevent.randomize; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN subevent.randomize IS 'ハイフン区切り（1-2,3-4）';


--
-- Name: COLUMN subevent.cond360; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN subevent.cond360 IS '360度用条件';


--
-- Name: COLUMN subevent.num; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN subevent.num IS '集計/結果出力時の番号';


--
-- Name: subevent_data; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE subevent_data (
    evid integer,
    serial_no character(8),
    seid integer,
    choice integer,
    other text,
    event_data_id integer
);


ALTER TABLE public.subevent_data OWNER TO www;

--
-- Name: subinquiry; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE subinquiry (
    subinqid integer NOT NULL,
    inqid integer,
    title text,
    body text,
    cdate timestamp without time zone,
    muid integer
);


ALTER TABLE public.subinquiry OWNER TO www;

--
-- Name: subinquiry_subinqid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE subinquiry_subinqid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.subinquiry_subinqid_seq OWNER TO www;

--
-- Name: subinquiry_subinqid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: www
--

ALTER SEQUENCE subinquiry_subinqid_seq OWNED BY subinquiry.subinqid;


--
-- Name: subinquiry_subinqid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('subinquiry_subinqid_seq', 1000000, true);


--
-- Name: uid_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE uid_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.uid_seq OWNER TO www;

--
-- Name: uid_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('uid_seq', 256, true);


--
-- Name: uid_seq_seq; Type: SEQUENCE; Schema: public; Owner: www
--

CREATE SEQUENCE uid_seq_seq
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.uid_seq_seq OWNER TO www;

--
-- Name: uid_seq_seq; Type: SEQUENCE SET; Schema: public; Owner: www
--

SELECT pg_catalog.setval('uid_seq_seq', 11, true);


--
-- Name: uniqrestore; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE uniqrestore (
    restore_id text
);


ALTER TABLE public.uniqrestore OWNER TO www;

--
-- Name: uniqserial; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE uniqserial (
    serial_no character(8)
);


ALTER TABLE public.uniqserial OWNER TO www;

--
-- Name: unique_uid; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE unique_uid (
    uid text NOT NULL
);


ALTER TABLE public.unique_uid OWNER TO www;

--
-- Name: usr; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE usr (
    div1 text,
    div2 text,
    div3 text,
    uid text,
    email text,
    serial_no character(8) NOT NULL,
    name text,
    pw text,
    evid integer,
    upload_id text,
    note text,
    mflag smallint DEFAULT 0 NOT NULL,
    sheet_type smallint DEFAULT 0 NOT NULL,
    pwmisscount smallint DEFAULT 0 NOT NULL,
    select_status smallint DEFAULT 0 NOT NULL,
    memo text,
    news_flag smallint DEFAULT 0 NOT NULL,
    name_ text,
    lang_flag smallint DEFAULT 0,
    lang_type smallint DEFAULT 0,
    login_flag smallint DEFAULT 0,
    test_flag smallint DEFAULT 0 NOT NULL,
    ext1 text,
    ext2 text,
    ext3 text,
    ext4 text,
    ext5 text,
    ext6 text,
    ext7 text,
    ext8 text,
    ext9 text,
    ext10 text,
    class text
);


ALTER TABLE public.usr OWNER TO www;

--
-- Name: COLUMN usr.div1; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.div1 IS '区分1';


--
-- Name: COLUMN usr.div2; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.div2 IS '区分2';


--
-- Name: COLUMN usr.div3; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.div3 IS '区分3';


--
-- Name: COLUMN usr.uid; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.uid IS 'ユーザID';


--
-- Name: COLUMN usr.email; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.email IS 'Eメールアドレス';


--
-- Name: COLUMN usr.serial_no; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.serial_no IS 'シリアルナンバー';


--
-- Name: COLUMN usr.name; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.name IS '名前1';


--
-- Name: COLUMN usr.pw; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.pw IS 'ログインPW';


--
-- Name: COLUMN usr.evid; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.evid IS 'アンケートID';


--
-- Name: COLUMN usr.upload_id; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.upload_id IS '登録ID';


--
-- Name: COLUMN usr.note; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.note IS '備考';


--
-- Name: COLUMN usr.mflag; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.mflag IS '360度_本人フラグ';


--
-- Name: COLUMN usr.sheet_type; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.sheet_type IS '360度_シートタイプ';


--
-- Name: COLUMN usr.pwmisscount; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.pwmisscount IS '360度_パスワード間違い回数';


--
-- Name: COLUMN usr.select_status; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.select_status IS '360度_選定状況フラグ';


--
-- Name: COLUMN usr.memo; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.memo IS '360度_メモ欄';


--
-- Name: COLUMN usr.news_flag; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.news_flag IS '360度_お知らせ非表示フラグ';


--
-- Name: COLUMN usr.name_; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.name_ IS '名前(ローマ字)';


--
-- Name: COLUMN usr.lang_flag; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.lang_flag IS '多言語対応 フラグ';


--
-- Name: COLUMN usr.lang_type; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.lang_type IS '言語タイプ';


--
-- Name: COLUMN usr.login_flag; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.login_flag IS 'ログインフラグ';


--
-- Name: COLUMN usr.test_flag; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr.test_flag IS '1=>テストユーザ';


--
-- Name: usr_relation; Type: TABLE; Schema: public; Owner: www; Tablespace: 
--

CREATE TABLE usr_relation (
    uid_a text NOT NULL,
    uid_b text NOT NULL,
    user_type smallint NOT NULL
);


ALTER TABLE public.usr_relation OWNER TO www;

--
-- Name: TABLE usr_relation; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON TABLE usr_relation IS '360度_ユーザの関連';


--
-- Name: COLUMN usr_relation.uid_a; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr_relation.uid_a IS '本人';


--
-- Name: COLUMN usr_relation.uid_b; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr_relation.uid_b IS '評価者';


--
-- Name: COLUMN usr_relation.user_type; Type: COMMENT; Schema: public; Owner: www
--

COMMENT ON COLUMN usr_relation.user_type IS '1=>上司 2=>部下 3=>同僚';


--
-- Name: auth_set_id; Type: DEFAULT; Schema: public; Owner: www
--

ALTER TABLE auth_set ALTER COLUMN auth_set_id SET DEFAULT nextval('auth_set_auth_set_id_seq'::regclass);


--
-- Name: asd_id; Type: DEFAULT; Schema: public; Owner: www
--

ALTER TABLE auth_set_div ALTER COLUMN asd_id SET DEFAULT nextval('auth_set_div_asd_id_seq'::regclass);


--
-- Name: cacheid; Type: DEFAULT; Schema: public; Owner: www
--

ALTER TABLE backup_event ALTER COLUMN cacheid SET DEFAULT nextval('backup_event_cacheid_seq'::regclass);


--
-- Name: inqid; Type: DEFAULT; Schema: public; Owner: www
--

ALTER TABLE inquiry ALTER COLUMN inqid SET DEFAULT nextval('inquiry_inqid_seq'::regclass);


--
-- Name: subinqid; Type: DEFAULT; Schema: public; Owner: www
--

ALTER TABLE subinquiry ALTER COLUMN subinqid SET DEFAULT nextval('subinquiry_subinqid_seq'::regclass);


--
-- Data for Name: auth_set_div; Type: TABLE DATA; Schema: public; Owner: www
--

COPY auth_set_div (muid, div1, div2, div3, asd_id) FROM stdin;
1	*	*	*	1
\.


--
-- Data for Name: musr; Type: TABLE DATA; Schema: public; Owner: www
--

COPY musr (muid, id, pw, div, name, flg, permitted, email, permitted_column) FROM stdin;
1	super	cbase	admin	admin	0	crm_enq0_client.php,crm_enq0.php,enq_event.php,enq_subevent.php,enq_subevent2.php,enq_subevent_batch.php,enq_setcond.php,enq_list.php,enq_cond.php,set_cond.php,cond_list.php,360_enq_import.php,360_enq_import_message.php,360_enq_update.php,enq_copy.php,360_message_view_client.php,360_message_edit.php,360_message_view.php,360_message_edit.php,360_message_import.php,360_file_edit.php,360_term_edit.php,crm_mf1.php,crm_mf2.php,enq_cond.php,enq_sqlsearch.php,enq_sqls_edit.php,mail_target_list.php,360_mail_target_list_dl.php,enq_mailrsv.php,crm_mr1.php,crm_mr1.php,crm_mr2.php,mail_target_list.php,mail_log_list.php,360_div_import.php,360_user_import.php,360_relation_import.php,360_admit_import.php,360_viewer_import.php,360_answer_import.php,360_div_search.php,360_div_edit.php,360_user_search.php,360_user_edit.php,360_user_relation_search.php,360_user_relation_view.php,360_user_relation_edit.php,360_target_relation_search.php,360_target_relation_view.php,360_target_relation_edit.php,360_admit_relation_search.php,360_admit_relation_edit.php,360_viewer_relation_search.php,360_viewer_relation_edit.php,360_user_evaluator_search.php,360_enq_search_all_withtest.php,enq_search_csv.php,360_enq_search_all.php,enq_search_csv.php,360_enq_search_div_withtest.php,360_enq_search_div.php,360_user_pw_search.php,360_user_pw_edit.php,360_rawdata_dl_menu.php,DLspecial.php,360_export_result_total.php,360_export_result_total2.php,360_export_result_total3.php,360_export_result_total4.php,360_comment_menu.php,360_comment_export.php,360_comment_import.php,360_dlcsv_menu.php,360_dlcsv.php,mng_mst.php,360_muser_import.php,mng_colmun_setting.php,mng_permit.php,mng_colmun_permit.php,360_musr_list.php,360_musr_authedit.php,360_muser_div_import.php,360_admin_operate.php,360_define_edit.php		email,name,pw,mypage,answer
\.



--
-- Name: auth_set_div_asd_id_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY auth_set_div
    ADD CONSTRAINT auth_set_div_asd_id_key UNIQUE (asd_id);


--
-- Name: auth_set_id_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY auth_set
    ADD CONSTRAINT auth_set_id_key UNIQUE (auth_set_id);


--
-- Name: backup_data_bdid_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY backup_data
    ADD CONSTRAINT backup_data_bdid_key UNIQUE (bdid);


--
-- Name: choice_unique; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY choice
    ADD CONSTRAINT choice_unique UNIQUE (evid, seid, num, div);


--
-- Name: cond_cnid; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY cond
    ADD CONSTRAINT cond_cnid UNIQUE (cnid);


--
-- Name: div_div3_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY div
    ADD CONSTRAINT div_div3_key UNIQUE (div3);


--
-- Name: event_data_event_data_id_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY event_data
    ADD CONSTRAINT event_data_event_data_id_key UNIQUE (event_data_id);


--
-- Name: event_design_evid_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY event_design
    ADD CONSTRAINT event_design_evid_key UNIQUE (evid);


--
-- Name: event_evid_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY event
    ADD CONSTRAINT event_evid_key UNIQUE (evid);


--
-- Name: event_rid_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY event
    ADD CONSTRAINT event_rid_key UNIQUE (rid);


--
-- Name: evid_serial_no_target_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY event_data
    ADD CONSTRAINT evid_serial_no_target_key UNIQUE (evid, serial_no, target);


--
-- Name: mail_format_mfid_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY mail_format
    ADD CONSTRAINT mail_format_mfid_key UNIQUE (mfid);


--
-- Name: mail_received_mail_received_id_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY mail_received
    ADD CONSTRAINT mail_received_mail_received_id_key UNIQUE (mail_received_id);


--
-- Name: mail_rsv_mrid_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY mail_rsv
    ADD CONSTRAINT mail_rsv_mrid_key UNIQUE (mrid);


--
-- Name: subevent_data_event_data_id_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE subevent_data ADD CONSTRAINT event_data_id_seid_choice_key UNIQUE(event_data_id, seid, choice);


--
-- Name: subevent_seid_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY subevent
    ADD CONSTRAINT subevent_seid_key UNIQUE (seid);


--
-- Name: uniqrestore_restore_id_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY uniqrestore
    ADD CONSTRAINT uniqrestore_restore_id_key UNIQUE (restore_id);


--
-- Name: unique_uid_pkey; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY unique_uid
    ADD CONSTRAINT unique_uid_pkey PRIMARY KEY (uid);


--
-- Name: usr_relation_uid_a_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY usr_relation
    ADD CONSTRAINT usr_relation_uid_a_key UNIQUE (uid_a, uid_b, user_type);


--
-- Name: usr_serial_no_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY usr
    ADD CONSTRAINT usr_serial_no_key UNIQUE (serial_no);


--
-- Name: usr_uid_key; Type: CONSTRAINT; Schema: public; Owner: www; Tablespace: 
--

ALTER TABLE ONLY usr
    ADD CONSTRAINT usr_uid_key UNIQUE (uid);


--
-- Name: div_div1; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX div_div1 ON div USING btree (div1);


--
-- Name: div_div2; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX div_div2 ON div USING btree (div2);


--
-- Name: div_div3; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX div_div3 ON div USING btree (div3);


--
-- Name: event_data_serial_no; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX event_data_serial_no ON event_data USING btree (serial_no);


--
-- Name: event_data_serial_no_evid_target_key; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE UNIQUE INDEX event_data_serial_no_evid_target_key ON event_data USING btree (serial_no, evid, target);


--
-- Name: mail_format_mfodr_key; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX mail_format_mfodr_key ON mail_format USING btree (mfodr);


--
-- Name: musr_muid_key; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE UNIQUE INDEX musr_muid_key ON musr USING btree (muid);


--
-- Name: seidx2; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX seidx2 ON subevent_data USING btree (seid);


--
-- Name: subevent_data_event_data_id; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX subevent_data_event_data_id ON subevent_data USING btree (event_data_id);


--
-- Name: us1; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE UNIQUE INDEX us1 ON uniqserial USING btree (serial_no);


--
-- Name: usr_div1; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX usr_div1 ON usr USING btree (div1);


--
-- Name: usr_div2; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX usr_div2 ON usr USING btree (div2);


--
-- Name: usr_div3; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX usr_div3 ON usr USING btree (div3);


--
-- Name: usr_relation_uid_a; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX usr_relation_uid_a ON usr_relation USING btree (uid_a);


--
-- Name: usr_relation_uid_b; Type: INDEX; Schema: public; Owner: www; Tablespace: 
--

CREATE INDEX usr_relation_uid_b ON usr_relation USING btree (uid_b);


--
-- PostgreSQL database dump complete
--
-- 2012/05/08 管理ユーザ パスワード改修
ALTER TABLE musr ADD COLUMN pwmisscount smallint NOT NULL DEFAULT 0;
ALTER TABLE musr ADD COLUMN pdate timestamp without time zone;
-- 2012/05/09 回答者 パスワード改修
ALTER TABLE usr ADD COLUMN pw_flag smallint NOT NULL DEFAULT 0;
COMMENT ON COLUMN usr.pw_flag IS 'パスワード変更フラグ';
-- 2012/05/28 部署マスタの多言語化
ALTER TABLE div ADD COLUMN div1_name_1 text;
ALTER TABLE div ADD COLUMN div1_name_2 text;
ALTER TABLE div ADD COLUMN div1_name_3 text;
ALTER TABLE div ADD COLUMN div1_name_4 text;
ALTER TABLE div ADD COLUMN div2_name_1 text;
ALTER TABLE div ADD COLUMN div2_name_2 text;
ALTER TABLE div ADD COLUMN div2_name_3 text;
ALTER TABLE div ADD COLUMN div2_name_4 text;
ALTER TABLE div ADD COLUMN div3_name_1 text;
ALTER TABLE div ADD COLUMN div3_name_2 text;
ALTER TABLE div ADD COLUMN div3_name_3 text;
ALTER TABLE div ADD COLUMN div3_name_4 text;