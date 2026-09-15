CREATE OR REPLACE FUNCTION pgsql_type(a_type varchar) RETURNS varchar AS $BODY$
DECLARE v_type varchar;
BEGIN
IF a_type='int8' THEN v_type:='bigint';
ELSIF a_type='int4' THEN v_type:='integer';
ELSIF a_type='int2' THEN v_type:='smallint';
ELSIF a_type='bpchar' THEN v_type:='char';
ELSE v_type:=a_type;
END IF;
RETURN v_type;
END;
$BODY$ LANGUAGE PLPGSQL;

CREATE TYPE "public"."tablestruct" AS ("fields_key_name" varchar(100), "fields_name" VARCHAR(200), "fields_type" VARCHAR(20), "fields_length" BIGINT, "fields_not_null" VARCHAR(10), "fields_default" VARCHAR(500), "fields_comment" VARCHAR(1000));

CREATE OR REPLACE FUNCTION "public"."table_msg" (a_schema_name varchar, a_table_name varchar) RETURNS SETOF "public"."tablestruct" AS $body$
DECLARE v_ret tablestruct; v_oid oid; v_sql varchar; v_rec RECORD; v_key varchar;
BEGIN
SELECT pg_class.oid INTO v_oid FROM pg_class INNER JOIN pg_namespace ON (pg_class.relnamespace = pg_namespace.oid AND lower(pg_namespace.nspname) = a_schema_name) WHERE pg_class.relname=a_table_name;
IF NOT FOUND THEN RETURN; END IF;
v_sql='SELECT pg_attribute.attname AS fields_name, pg_attribute.attnum AS fields_index, pgsql_type(pg_type.typname::varchar) AS fields_type, pg_attribute.atttypmod-4 as fields_length, CASE WHEN pg_attribute.attnotnull THEN ''not null'' ELSE '''' END AS fields_not_null, pg_get_expr(pg_attrdef.adbin, pg_attribute.attrelid) AS fields_default, pg_description.description AS fields_comment FROM pg_attribute INNER JOIN pg_class ON pg_attribute.attrelid = pg_class.oid INNER JOIN pg_type ON pg_attribute.atttypid = pg_type.oid LEFT OUTER JOIN pg_attrdef ON pg_attrdef.adrelid = pg_class.oid AND pg_attrdef.adnum = pg_attribute.attnum LEFT OUTER JOIN pg_description ON pg_description.objoid = pg_class.oid AND pg_description.objsubid = pg_attribute.attnum WHERE pg_attribute.attnum > 0 AND attisdropped <> ''t'' AND pg_class.oid = ' || v_oid || ' ORDER BY pg_attribute.attnum';
FOR v_rec IN EXECUTE v_sql LOOP
v_ret.fields_name=v_rec.fields_name;
v_ret.fields_type=v_rec.fields_type;
IF v_rec.fields_length > 0 THEN v_ret.fields_length:=v_rec.fields_length;
ELSE v_ret.fields_length:=NULL;
END IF;
v_ret.fields_not_null=v_rec.fields_not_null;
v_ret.fields_default=v_rec.fields_default;
v_ret.fields_comment=v_rec.fields_comment;
SELECT constraint_name INTO v_key FROM information_schema.key_column_usage WHERE table_schema=a_schema_name AND table_name=a_table_name AND column_name=v_rec.fields_name;
IF FOUND THEN v_ret.fields_key_name=v_key;
ELSE v_ret.fields_key_name='';
END IF;
RETURN NEXT v_ret;
END LOOP;
RETURN;
END;
$body$ LANGUAGE 'plpgsql' VOLATILE CALLED ON NULL INPUT SECURITY INVOKER;

COMMENT ON FUNCTION "public"."table_msg"(a_schema_name varchar, a_table_name varchar) IS '获得表信息';

CREATE OR REPLACE FUNCTION "public"."table_msg" (a_table_name varchar) RETURNS SETOF "public"."tablestruct" AS $body$
DECLARE v_ret tablestruct;
BEGIN
FOR v_ret IN SELECT * FROM table_msg('public',a_table_name) LOOP
    RETURN NEXT v_ret;
END LOOP;
RETURN;
END;
$body$ LANGUAGE 'plpgsql' VOLATILE CALLED ON NULL INPUT SECURITY INVOKER;

COMMENT ON FUNCTION "public"."table_msg"(a_table_name varchar) IS '获得表信息';

CREATE EXTENSION IF NOT EXISTS vector;
DO $$
BEGIN
IF NOT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'vector') THEN
CREATE EXTENSION vector;
RAISE NOTICE 'vector 扩展已安装';
ELSE
RAISE NOTICE 'vector 扩展已存在';
END IF;
END $$;

DO $$
BEGIN
IF NOT EXISTS (SELECT 1 FROM pg_extension WHERE extname = 'zhparser') THEN
BEGIN
CREATE EXTENSION zhparser;
RAISE NOTICE 'zhparser 扩展已安装';
EXCEPTION WHEN OTHERS THEN
RAISE EXCEPTION '无法安装 zhparser 扩展: %', SQLERRM;
END;
ELSE
RAISE NOTICE 'zhparser 扩展已存在';
END IF;
END $$;

DO $$
BEGIN
IF NOT EXISTS (SELECT 1 FROM pg_ts_config WHERE cfgname = 'zh_en') THEN
CREATE TEXT SEARCH CONFIGURATION zh_en (PARSER = zhparser);
ALTER TEXT SEARCH CONFIGURATION zh_en ADD MAPPING FOR n,v,a,i,l,j,s,p,t,c,e,u,x,z WITH simple;
RAISE NOTICE '全文搜索配置【zh_en】已创建';
ELSE
RAISE NOTICE '全文搜索配置【zh_en】已存在';
END IF;
END $$;

DROP TABLE IF EXISTS "public"."la_kb_embedding";
CREATE TABLE "public"."la_kb_embedding" (
"uuid" uuid NOT NULL,
"kb_id" int4 NOT NULL,
"fd_id" int4 NOT NULL DEFAULT 0,
"user_id" int4 NOT NULL DEFAULT 0,
"emb_model_id" int4 NOT NULL DEFAULT 0,
"index" int4 NOT NULL DEFAULT 1,
"code" varchar(100) COLLATE "pg_catalog"."default" NOT NULL DEFAULT ''::character varying,
"salt" varchar(100) COLLATE "pg_catalog"."default" NOT NULL DEFAULT ''::character varying,
"channel" varchar(100) COLLATE "pg_catalog"."default" NOT NULL DEFAULT ''::character varying,
"model" varchar(100) COLLATE "pg_catalog"."default" NOT NULL DEFAULT ''::character varying,
"dimension" int4 NOT NULL DEFAULT 0,
"question" text COLLATE "pg_catalog"."default" DEFAULT ''::text,
"answer" text COLLATE "pg_catalog"."default" DEFAULT ''::text,
"annex" text COLLATE "pg_catalog"."default" DEFAULT ''::text,
"phrases" tsvector,
"embedding" "public"."vector",
"tokens" numeric(15,7) NOT NULL DEFAULT 0,
"error" varchar(500) COLLATE "pg_catalog"."default" DEFAULT ''::character varying,
"status" int2 NOT NULL DEFAULT 0,
"is_delete" int2 NOT NULL DEFAULT 0,
"create_time" int4 NOT NULL DEFAULT 0,
"update_time" int4 NOT NULL DEFAULT 0,
"delete_time" int4 NOT NULL DEFAULT 0,
CONSTRAINT "la_kb_embedding_pkey" PRIMARY KEY ("uuid")
);

CREATE INDEX "kb_idx" ON "public"."la_kb_embedding" USING btree ("kb_id" "pg_catalog"."int4_ops" ASC NULLS LAST);
ALTER TABLE "public"."la_kb_embedding" OWNER TO "postgres";
COMMENT ON COLUMN "public"."la_kb_embedding"."kb_id" IS '知识库ID';
COMMENT ON COLUMN "public"."la_kb_embedding"."fd_id" IS '文件的ID';
COMMENT ON COLUMN "public"."la_kb_embedding"."user_id" IS '用户的ID';
COMMENT ON COLUMN "public"."la_kb_embedding"."emb_model_id" IS '向量模型ID';
COMMENT ON COLUMN "public"."la_kb_embedding"."index" IS '下标页码';
COMMENT ON COLUMN "public"."la_kb_embedding"."code" IS '批次编号';
COMMENT ON COLUMN "public"."la_kb_embedding"."salt" IS '问题的盐';
COMMENT ON COLUMN "public"."la_kb_embedding"."channel" IS '训练渠道';
COMMENT ON COLUMN "public"."la_kb_embedding"."model" IS '训练模型';
COMMENT ON COLUMN "public"."la_kb_embedding"."dimension" IS '向量维度';
COMMENT ON COLUMN "public"."la_kb_embedding"."question" IS '问题';
COMMENT ON COLUMN "public"."la_kb_embedding"."answer" IS '答复';
COMMENT ON COLUMN "public"."la_kb_embedding"."annex" IS '附件';
COMMENT ON COLUMN "public"."la_kb_embedding"."phrases" IS '分词';
COMMENT ON COLUMN "public"."la_kb_embedding"."embedding" IS '向量';
COMMENT ON COLUMN "public"."la_kb_embedding"."tokens" IS '消耗tokens';
COMMENT ON COLUMN "public"."la_kb_embedding"."error" IS '错误';
COMMENT ON COLUMN "public"."la_kb_embedding"."status" IS '训练状态: [0=等待学习, 1=学习中, 2=学习成功, 3=学习失败]';
COMMENT ON COLUMN "public"."la_kb_embedding"."is_delete" IS '是否删除: [0=否, 1=是]';
COMMENT ON COLUMN "public"."la_kb_embedding"."create_time" IS '创建时间';
COMMENT ON COLUMN "public"."la_kb_embedding"."update_time" IS '更新时间';
COMMENT ON COLUMN "public"."la_kb_embedding"."delete_time" IS '删除时间';
