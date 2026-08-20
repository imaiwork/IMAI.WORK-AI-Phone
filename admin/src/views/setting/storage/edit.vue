<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            title="设置存储"
            :async="true"
            width="550px"
            @confirm="handleSubmit"
            @close="handleClose">
            <el-form ref="formRef" :model="formData" label-width="160px" :rules="formRules">
                <el-form-item label="存储方式" prop="engine">
                    <div>
                        <el-radio-group :model-value="1">
                            <el-radio :value="1">{{ getStorageInfo?.name }} </el-radio>
                        </el-radio-group>
                        <div class="form-tips">{{ getStorageInfo?.tips }}</div>
                    </div>
                </el-form-item>
                <div v-if="formData.engine !== 'local'">
                    <el-form-item label=" 存储空间名称" prop="bucket">
                        <div class="flex-1">
                            <el-input v-model="formData.bucket" placeholder="请输入存储空间名称(Bucket)" clearable />
                        </div>
                    </el-form-item>
                    <el-form-item label="ACCESS_KEY" prop="access_key">
                        <el-input v-model="formData.access_key" placeholder="请输入ACCESS_KEY(AK)" clearable />
                    </el-form-item>
                    <el-form-item label="SECRET_KEY" prop="secret_key">
                        <el-input v-model="formData.secret_key" placeholder="请输入SECRET_KEY(SK)" clearable />
                    </el-form-item>
                    <el-form-item label="空间域名" prop="domain">
                        <div class="flex-1">
                            <div>
                                <el-input v-model="formData.domain" placeholder="请输入空间域名(Domain)" clearable />
                            </div>
                            <div class="form-tips">请补全http://或https://，例如https://static.cloud.com</div>
                        </div>
                    </el-form-item>
                    <el-form-item v-if="formData.engine == StorageEnum.QCLOUD" label="REGION" prop="region">
                        <el-input v-model="formData.region" placeholder="请输入region" clearable />
                    </el-form-item>

                    <!-- 七牛 / 腾讯：媒体处理固定本地，不可切换 -->
                    <el-form-item
                        v-if="[StorageEnum.QINIU, StorageEnum.QCLOUD].includes(formData.engine)"
                        label="媒体处理">
                        <div class="flex-1">
                            <el-tag type="info">本地 ffmpeg（固定）</el-tag>
                            <div class="form-tips">七牛/腾讯仅作文件存储，切割与转码固定走本地，不支持切换至云处理</div>
                        </div>
                    </el-form-item>

                    <!-- 仅阿里云可切换本地 / OSS(MPS) -->
                    <template v-if="formData.engine === StorageEnum.ALIYUN">
                        <el-form-item label="媒体处理" prop="media_process">
                            <div class="flex-1">
                                <el-radio-group v-model="formData.media_process">
                                    <el-radio value="local">本地 ffmpeg</el-radio>
                                    <el-radio value="oss">OSS 切割/转码(MPS)</el-radio>
                                </el-radio-group>
                                <div class="form-tips">
                                    默认本地。选择 OSS 时需配置 MPS；管道与转码模板切割/转码共用。
                                </div>
                            </div>
                        </el-form-item>
                        <template v-if="formData.media_process === 'oss'">
                            <el-form-item label="Location(位置)" prop="Location">
                                <div class="flex-1">
                                    <el-input v-model="formData.Location" placeholder="例如 oss-cn-beijing" clearable />
                                    <div class="form-tips">须与 Bucket 地域一致，格式：oss-cn-xxx</div>
                                </div>
                            </el-form-item>
                            <el-form-item label="PipelineId(管道ID)" prop="PipelineId">
                                <el-input v-model="formData.PipelineId" placeholder="请输入 MPS 管道 ID" clearable />
                            </el-form-item>
                            <el-form-item label="TemplateId(模板ID)" prop="TemplateId">
                                <div class="flex-1">
                                    <el-input
                                        v-model="formData.TemplateId"
                                        placeholder="请输入 MPS 转码模板 ID"
                                        clearable />
                                    <div class="form-tips">建议与项目视频规范对齐（H.264 / AAC / mp4）</div>
                                </div>
                            </el-form-item>
                        </template>
                    </template>
                </div>
                <el-form-item label="状态" prop="status">
                    <el-radio-group v-model="formData.status">
                        <el-radio :label="0">关闭</el-radio>
                        <el-radio :label="1">开启</el-radio>
                    </el-radio-group>
                </el-form-item>
            </el-form>
        </popup>
    </div>
</template>
<script lang="ts" setup>
import type { FormInstance, FormRules } from "element-plus";
import Popup from "@/components/popup/index.vue";
import { storageDetail, storageSetup, type MediaProcessMode, type StorageSetupParams } from "@/api/setting/storage";

enum StorageEnum {
    LOCAL = "local",
    QINIU = "qiniu",
    ALIYUN = "aliyun",
    QCLOUD = "qcloud",
}

interface StorageFormData {
    engine: string;
    bucket: string;
    access_key: string;
    secret_key: string;
    domain: string;
    region: string;
    status: number;
    PipelineId: string;
    Location: string;
    TemplateId: string;
    media_process: MediaProcessMode;
}

const emit = defineEmits<{
    success: [];
}>();
const formRef = shallowRef<FormInstance>();
const popupRef = shallowRef<InstanceType<typeof Popup>>();
const formData = reactive<StorageFormData>({
    engine: "",
    bucket: "",
    access_key: "",
    secret_key: "",
    domain: "",
    region: "",
    status: 0,
    PipelineId: "",
    Location: "",
    TemplateId: "",
    media_process: "local",
});

const storageArr = [
    {
        name: '本地存储',
        type: StorageEnum.LOCAL,
        tips: '本地存储方式不需要配置其他参数'
    },
    {
        name: '七牛云存储',
        type: StorageEnum.QINIU,
        tips: "仅文件存储；切割/转码固定本地 ffmpeg，不支持云处理切换",
    },
    {
        name: '阿里云OSS',
        type: StorageEnum.ALIYUN,
        tips: "文件存阿里云；切割/转码可选本地 ffmpeg 或 OSS(MPS)",
    },
    {
        name: '腾讯云OSS',
        type: StorageEnum.QCLOUD,
        tips: "仅文件存储；切割/转码固定本地 ffmpeg，不支持云处理切换",
    },
];

const requireWhenAliyunOss = (_rule: unknown, value: string, callback: (error?: Error) => void) => {
    if (formData.engine !== StorageEnum.ALIYUN || formData.media_process !== "oss") {
        callback();
        return;
    }
    if (!String(value || "").trim()) {
        callback(new Error("选择 OSS 媒体处理时必填"));
        return;
    }
    callback();
};

const validateLocation = (_rule: unknown, value: string, callback: (error?: Error) => void) => {
    if (formData.engine !== StorageEnum.ALIYUN || formData.media_process !== "oss") {
        callback();
        return;
    }
    const location = String(value || "").trim();
    if (!location) {
        callback(new Error("请输入 Location"));
        return;
    }
    if (!/^oss-[a-z0-9-]+$/.test(location)) {
        callback(new Error("Location 格式不正确，示例：oss-cn-beijing"));
        return;
    }
    callback();
};

const formRules = computed<FormRules>(() => ({
    bucket: [{ required: true, message: "请输入存储空间名称", trigger: "blur" }],
    access_key: [{ required: true, message: "请输入ACCESS_KEY", trigger: "blur" }],
    secret_key: [{ required: true, message: "请输入SECRET_KEY", trigger: "blur" }],
    domain: [{ required: true, message: "请输入空间域名", trigger: "blur" }],
    region: [{ required: true, message: "请输入REGION", trigger: "blur" }],
    media_process: [{ required: true, message: "请选择媒体处理方式", trigger: "change" }],
    Location: [{ validator: validateLocation, trigger: "blur" }],
    PipelineId: [{ validator: requireWhenAliyunOss, trigger: "blur" }],
    TemplateId: [{ validator: requireWhenAliyunOss, trigger: "blur" }],
}));

const getStorageInfo = computed(() => {
    return storageArr.find((item) => item.type == formData.engine);
});

watch(
    () => formData.media_process,
    () => {
        formRef.value?.clearValidate(["Location", "PipelineId", "TemplateId"]);
    },
);

/** 按引擎组装 setup 入参 */
const buildSetupPayload = (): StorageSetupParams => {
    const payload: StorageSetupParams = {
        engine: formData.engine,
        status: Number(formData.status),
    };

    if (formData.engine === StorageEnum.LOCAL) {
        return payload;
    }

    payload.bucket = formData.bucket.trim();
    payload.access_key = formData.access_key.trim();
    payload.secret_key = formData.secret_key.trim();
    payload.domain = formData.domain.trim();

    if (formData.engine === StorageEnum.ALIYUN) {
        payload.media_process = formData.media_process === "oss" ? "oss" : "local";
        payload.Location = formData.Location.trim();
        payload.PipelineId = formData.PipelineId.trim();
        payload.TemplateId = formData.TemplateId.trim();
        return payload;
    }

    // 七牛 / 腾讯：不传 media_process 与 MPS 字段，服务端固定本地
    if (formData.engine === StorageEnum.QCLOUD) {
        payload.region = formData.region.trim();
    }

    return payload;
};

const handleSubmit = async () => {
    await formRef.value?.validate();
    await storageSetup(buildSetupPayload());
    popupRef.value?.close();
    emit("success");
};

const getDetail = async () => {
    const data: Record<string, any> =
        (await storageDetail({
            engine: formData.engine,
        })) || {};

    formData.bucket = data.bucket || "";
    formData.access_key = data.access_key || "";
    formData.secret_key = data.secret_key || "";
    formData.domain = data.domain || "";
    formData.region = data.region || "";
    formData.status = Number(data.status ?? 0);
    formData.PipelineId = data.PipelineId || "";
    formData.Location = data.Location || "";
    formData.TemplateId = data.TemplateId || "";
    // 仅阿里云可读 oss；七牛/腾讯强制展示/提交 local
    formData.media_process = formData.engine === StorageEnum.ALIYUN && data.media_process === "oss" ? "oss" : "local";
};

const open = (type: string) => {
    formData.engine = type;
    formData.media_process = "local";
    popupRef.value?.open();
    getDetail();
};

const handleClose = () => {
    formRef.value?.resetFields();
    formData.media_process = "local";
    formData.Location = "";
    formData.PipelineId = "";
    formData.TemplateId = "";
};

defineExpose({
    open
})
</script>
