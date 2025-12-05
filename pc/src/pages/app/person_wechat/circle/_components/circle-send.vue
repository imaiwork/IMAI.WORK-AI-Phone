<template>
    <div class="h-full bg-white w-full overflow-hidden flex flex-col">
        <!-- 头部 -->
        <div class="bg-primary h-[48px] flex items-center justify-center font-bold text-white text-xl flex-shrink-0">
            发送朋友圈
        </div>

        <!-- 表单主体 -->
        <div class="grow min-h-0">
            <ElScrollbar>
                <ElForm
                    ref="formRef"
                    :model="formData"
                    :rules="!!formData.id ? {} : rules"
                    label-width="0"
                    :disabled="!!formData.id">
                    <div class="px-6 py-3 flex flex-col">
                        <!-- 区域：基础文本内容 -->
                        <ElFormItem prop="content">
                            <div class="w-full">
                                <div class="flex items-center gap-x-3">
                                    <div class="bg-primary h-[14px] w-[4px]"></div>
                                    <div class="text-lg">朋友圈基础文本内容</div>
                                </div>
                                <div class="mt-4 relative">
                                    <ElInput
                                        ref="contentRef"
                                        v-model="formData.content"
                                        type="textarea"
                                        resize="none"
                                        placeholder="点击输入您要发送的文本内容"
                                        :autosize="{ minRows: 6, maxRows: 15 }"
                                        maxlength="300" />
                                    <div class="absolute bottom-1 left-2 flex items-center gap-x-2">
                                        <ElPopover
                                            placement="bottom"
                                            width="466"
                                            trigger="click"
                                            :show-arrow="false"
                                            :popper-style="{ padding: 0 }">
                                            <template #reference>
                                                <div
                                                    class="rounded-lg hover:bg-token-sidebar-surface-secondary p-2 cursor-pointer">
                                                    <Icon name="local-icon-phiz" :size="24" />
                                                </div>
                                            </template>
                                            <EmojiContainer @chooseEmoji="handleChooseEmoji" />
                                        </ElPopover>
                                    </div>
                                </div>
                            </div>
                        </ElFormItem>

                        <!-- 区域：附加内容 -->
                        <ElFormItem prop="attachment_content">
                            <div class="w-full">
                                <div class="flex items-center gap-x-3">
                                    <div class="bg-primary h-[14px] w-[4px]"></div>
                                    <div class="text-lg">朋友圈附加内容</div>
                                </div>
                                <div class="mt-4">
                                    <ElRadioGroup v-model="formData.attachment_type">
                                        <ElRadio
                                            v-for="item in postTypeList"
                                            :key="item.value"
                                            :value="item.value"
                                            :disabled="item.disabled">
                                            {{ item.label }}
                                        </ElRadio>
                                    </ElRadioGroup>
                                </div>
                                <div class="mt-4">
                                    <!-- 图片附件 -->
                                    <template v-if="formData.attachment_type === MaterialTypeEnum.IMAGE">
                                        <div class="flex mb-4">
                                            <upload
                                                v-if="imageUploadLimit > 0"
                                                class="img-upload"
                                                :limit="imageUploadLimit"
                                                :show-file-list="false"
                                                show-progress
                                                @success="uploadSuccess">
                                                <div class="upload-select">
                                                    <Icon name="local-icon-image_add" color="#999999" :size="40" />
                                                    <span class="text-[#999999] mt-2">点击上传图片</span>
                                                    <span class="text-[#999999]">
                                                        或<a @click.stop="openMaterialPicker" class="text-primary"
                                                            >从素材库选择</a
                                                        >
                                                    </span>
                                                </div>
                                            </upload>
                                        </div>
                                        <div class="grid grid-cols-6 gap-5">
                                            <div
                                                v-for="(item, index) in assetData.image"
                                                :key="index"
                                                class="w-[64px] h-[64px] bg-[#F7F7F7] rounded-lg p-1 relative">
                                                <ElImage
                                                    :src="item"
                                                    :preview-src-list="[item]"
                                                    preview-teleported
                                                    class="h-full rounded-lg w-full object-cover" />
                                                <div
                                                    v-if="!formData.id"
                                                    class="absolute -top-2 -right-2 cursor-pointer"
                                                    @click="handleRemoveImage(index)">
                                                    <Icon
                                                        name="local-icon-close_circle_fill"
                                                        :size="20"
                                                        color="#D43030" />
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- 视频附件 -->
                                    <template v-if="formData.attachment_type === MaterialTypeEnum.VIDEO">
                                        <upload
                                            type="video"
                                            :limit="1"
                                            :show-file-list="false"
                                            show-progress
                                            @success="uploadSuccess">
                                            <div class="upload-select">
                                                <template v-if="assetData.video">
                                                    <div
                                                        class="absolute -top-2.5 -right-2.5 cursor-pointer"
                                                        @click.stop="handleRemoveVideo">
                                                        <Icon
                                                            name="local-icon-close_circle_fill"
                                                            :size="20"
                                                            color="#D43030" />
                                                    </div>
                                                    <video :src="assetData.video" class="w-full h-full" />
                                                </template>
                                                <template v-else>
                                                    <Icon name="local-icon-video_add" color="#999999" :size="40" />
                                                    <span class="text-[#999999] mt-2">点击上传视频</span>
                                                    <span class="text-[#999999]">
                                                        或<a @click.stop="openMaterialPicker" class="text-primary"
                                                            >从素材库选择</a
                                                        >
                                                    </span>
                                                </template>
                                            </div>
                                        </upload>
                                    </template>

                                    <!-- 链接/小程序附件 -->
                                    <template v-if="isLinkOrMiniProgram">
                                        <div class="upload-select cursor-pointer" @click="openMaterialPicker">
                                            <template v-if="!hasLinkOrMiniProgramAsset">
                                                <Icon name="local-icon-file2" color="#999999" :size="32"></Icon>
                                                <a class="text-primary mt-3">点击从素材库选择</a>
                                            </template>
                                            <div
                                                v-else
                                                class="p-2 bg-primary-light-7 h-full w-full rounded-lg relative">
                                                <link-card
                                                    v-if="formData.attachment_type === MaterialTypeEnum.LINK"
                                                    :title="assetData.link.title"
                                                    :img="assetData.link.pic"
                                                    :desc="assetData.link.desc" />
                                                <mini-program-card
                                                    v-else-if="
                                                        formData.attachment_type === MaterialTypeEnum.MINI_PROGRAM
                                                    "
                                                    :title="assetData.mini_program.title"
                                                    :pic="assetData.mini_program.pic"
                                                    :link="assetData.mini_program.link" />
                                                <div
                                                    class="absolute -top-2 -right-2 cursor-pointer"
                                                    @click.stop="handleRemoveLinkOrMiniProgram">
                                                    <Icon
                                                        name="local-icon-close_circle_fill"
                                                        :size="20"
                                                        color="#D43030" />
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </ElFormItem>

                        <!-- 区域：附加评论 -->
                        <ElFormItem prop="comment">
                            <div class="w-full">
                                <div class="flex items-center gap-x-3">
                                    <div class="bg-primary h-[14px] w-[4px]"></div>
                                    <div class="text-lg">朋友圈附加评论</div>
                                </div>
                                <div class="mt-4">
                                    <ElInput
                                        v-model="formData.comment"
                                        type="textarea"
                                        placeholder="点击输入您需要附加的评论，多个评论之间用##隔开"
                                        resize="none"
                                        :rows="4" />
                                </div>
                            </div>
                        </ElFormItem>

                        <!-- 区域：发送策略 -->
                        <div class="w-full">
                            <div class="flex items-center gap-x-3">
                                <div class="bg-primary h-[14px] w-[4px]"></div>
                                <div class="text-lg">朋友圈发送策略</div>
                            </div>
                            <div class="mt-4">
                                <ElFormItem :prop="formData.task_type === 1 ? 'send_time' : ''">
                                    <ElRadioGroup v-model="formData.task_type">
                                        <ElRadio :value="0">立即发送</ElRadio>
                                        <ElRadio :value="1">
                                            <ElDatePicker
                                                v-model="formData.send_time"
                                                type="datetime"
                                                :disabled="formData.task_type === 0"
                                                :disabled-date="getDisabledDate"
                                                value-format="YYYY-MM-DD HH:mm:ss"
                                                placeholder="选择发送时间" />
                                        </ElRadio>
                                    </ElRadioGroup>
                                </ElFormItem>
                            </div>
                        </div>

                        <!-- 区域：选择账号 -->
                        <ElFormItem v-if="isShowWeChat" prop="wechat_ids">
                            <div class="w-full">
                                <div class="flex items-center gap-x-3">
                                    <div class="bg-primary h-[14px] w-[4px]"></div>
                                    <div class="text-lg">请选择发送账号</div>
                                </div>
                                <div class="mt-4">
                                    <ElSelect v-model="formData.wechat_ids" multiple filterable clearable>
                                        <ElOption
                                            v-for="item in optionsData.wechatLists"
                                            :key="item.wechat_id"
                                            :value="item.wechat_id"
                                            :label="item.wechat_nickname" />
                                    </ElSelect>
                                </div>
                            </div>
                        </ElFormItem>
                    </div>
                </ElForm>
            </ElScrollbar>
        </div>

        <!-- 底部 -->
        <div v-if="!formData.id" class="my-4 flex justify-center">
            <ElButton type="primary" :loading="isLock" @click="lockFn">立即创建</ElButton>
        </div>
    </div>

    <!-- 素材选择器弹窗 -->
    <material-picker
        v-if="showMaterialPicker"
        ref="materialPickerRef"
        mode="page"
        :limit="formData.attachment_type === MaterialTypeEnum.IMAGE ? imageUploadLimit : 1"
        :type="formData.attachment_type"
        @close="showMaterialPicker = false"
        @select="handleConfirmMaterial" />
</template>

<script setup lang="ts">
import { dayjs, type FormInstance, type InputInstance } from "element-plus";
import { circleTaskAdd } from "@/api/person_wechat";
import { MaterialTypeEnum } from "@/pages/app/person_wechat/_enums";
import EmojiContainer from "../../_components/emoji.vue";
import LinkCard from "../../_components/link-card.vue";
import MiniProgramCard from "../../_components/mini-program-card.vue";
import MaterialPicker from "../../_components/material-picker.vue";
import useGlobalSettings from "../../_hooks/useGlobalSettings";
import { setRangeText } from "@/utils/dom";
import { computed, reactive, ref, shallowRef, nextTick } from "vue";

// ** 1. 类型定义与组件接口 **

type FormData = {
    id?: number;
    content: string;
    task_type: 0 | 1;
    attachment_type: MaterialTypeEnum;
    attachment_content: any;
    comment: string;
    send_time: string;
    wechat_ids?: string[];
};

const props = withDefaults(
    defineProps<{
        modelValue: FormData;
        isShowWeChat?: boolean;
    }>(),
    {
        isShowWeChat: true,
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: FormData): void;
    (e: "success"): void;
}>();

// ** 2. 全局与插件实例 **

const nuxtApp = useNuxtApp();

// ** 3. 表单状态与验证 **

const formData = computed({
    get: () => props.modelValue,
    set: (value: FormData) => emit("update:modelValue", value),
});

const formRef = shallowRef<FormInstance>();
const rules = {
    content: [{ required: true, message: "请输入朋友圈基础文本内容" }],
    send_time: [
        { required: true, message: "请选择朋友圈发送时间" },
        {
            validator: (rule, value, callback) => {
                if (value && dayjs(value).isBefore(dayjs())) {
                    callback(new Error("发送时间不能小于当前时间"));
                } else {
                    callback();
                }
            },
        },
    ],
    wechat_ids: [{ required: true, message: "请选择发送账号" }],
};

const assetData = reactive({
    image: [] as string[],
    video: "",
    link: {} as Record<string, any>,
    mini_program: {} as Record<string, any>,
});

// ** 4. 内容与表情符号状态 **

const contentRef = ref<InputInstance>();

const handleChooseEmoji = ({ emoji }: any) => {
    if (contentRef.value?.textarea) {
        formData.value.content = setRangeText(contentRef.value.textarea, emoji);
    }
};

// ** 5. 附件状态与逻辑 **

const postTypeList = [
    { label: "附加图片", value: MaterialTypeEnum.IMAGE, disabled: false },
    { label: "附加视频", value: MaterialTypeEnum.VIDEO, disabled: false },
    { label: "附加链接", value: MaterialTypeEnum.LINK, disabled: false },
    { label: "附加小程序", value: MaterialTypeEnum.MINI_PROGRAM, disabled: false },
];

// 计算最大可上传图片数量
const imageUploadLimit = computed(() => 9 - assetData.image.length);

// 判断当前附件类型是否为链接或小程序
const isLinkOrMiniProgram = computed(() => {
    const { attachment_type } = formData.value;
    return [MaterialTypeEnum.LINK, MaterialTypeEnum.MINI_PROGRAM].includes(attachment_type);
});

// 判断是否已有链接或小程序资源
const hasLinkOrMiniProgramAsset = computed(() => {
    const { attachment_type } = formData.value;
    return attachment_type === MaterialTypeEnum.LINK
        ? Boolean(assetData.link.title)
        : Boolean(assetData.mini_program.title);
});

// 禁用当前日期之前的日期
const getDisabledDate = (time: Date) => time.getTime() < dayjs().startOf("day").valueOf();

/**
 * 处理文件上传成功事件
 * @param result 上传结果
 */
const uploadSuccess = (result: { data: { uri: string } }) => {
    const { uri } = result.data;
    const { attachment_type } = formData.value;

    switch (attachment_type) {
        case MaterialTypeEnum.IMAGE:
            if (imageUploadLimit.value > 0) {
                assetData.image.push(uri);
            }
            break;
        case MaterialTypeEnum.VIDEO:
            assetData.video = uri;
            break;
    }
};

/**
 * 删除图片
 * @param index 图片索引
 */
const handleRemoveImage = (index: number) => {
    nuxtApp.$confirm({
        message: "确定删除该图片吗？",
        onConfirm: () => {
            assetData.image.splice(index, 1);
        },
    });
};

/**
 * 删除视频
 */
const handleRemoveVideo = () => {
    assetData.video = "";
};

/**
 * 删除链接或小程序
 */
const handleRemoveLinkOrMiniProgram = () => {
    if (formData.value.attachment_type === MaterialTypeEnum.LINK) {
        assetData.link = {};
    } else {
        assetData.mini_program = {};
    }
};

// ** 6. 素材选择器逻辑 **

const showMaterialPicker = ref(false);
const materialPickerRef = ref<InstanceType<typeof MaterialPicker>>();

/**
 * 打开素材选择器
 */
const openMaterialPicker = async () => {
    // 如果是编辑模式，不允许打开素材选择器
    if (formData.value.id) return;

    showMaterialPicker.value = true;
    await nextTick();
    materialPickerRef.value?.open();
};

/**
 * 处理素材选择确认
 * @param result 选择的素材结果
 */
const handleConfirmMaterial = (result: any) => {
    const { attachment_type } = formData.value;

    switch (attachment_type) {
        case MaterialTypeEnum.IMAGE:
            if (imageUploadLimit.value > 0) {
                // 限制添加的图片数量
                const lists = result.splice(0, imageUploadLimit.value);
                // 提取图片URL并添加到图片列表
                assetData.image.push(...lists.map((item: any) => item.file_url));
            }
            break;

        case MaterialTypeEnum.VIDEO:
            assetData.video = result.file_url;
            break;

        case MaterialTypeEnum.LINK:
            assetData.link = {
                title: result.file_name,
                desc: result.ext_info.link_desc,
                link: result.ext_info.link,
                pic: result.file_url,
            };
            break;

        case MaterialTypeEnum.MINI_PROGRAM:
            assetData.mini_program = {
                title: result.file_name,
                pic: result.file_url,
                link: result.ext_info.mini_program_path,
                appid: result.ext_info.mini_program_appid,
            };
            break;
    }

    // 关闭素材选择器
    showMaterialPicker.value = false;
};

// ** 7. 提交策略与全局数据 **

const { optionsData } = useGlobalSettings();

// ** 8. 表单提交 **

/**
 * 准备附件内容
 */
const prepareAttachmentContent = () => {
    const { attachment_type } = formData.value;

    switch (attachment_type) {
        case MaterialTypeEnum.IMAGE:
            return assetData.image;
        case MaterialTypeEnum.VIDEO:
            return assetData.video;
        case MaterialTypeEnum.LINK:
            return assetData.link;
        case MaterialTypeEnum.MINI_PROGRAM:
            return assetData.mini_program;
        default:
            return null;
    }
};

/**
 * 处理表单提交
 */
const handleCreate = async () => {
    // 表单验证
    await formRef.value?.validate();
    try {
        // 设置附件内容
        formData.value.attachment_content = prepareAttachmentContent();

        // 判断附加内容是不是为空
        if (!formData.value.attachment_content) {
            formData.value.attachment_type = MaterialTypeEnum.TEXT;
        }

        // 如果是立即发送，清空发送时间
        if (formData.value.task_type === 0) {
            formData.value.send_time = "";
        }

        // 提交表单
        await circleTaskAdd(formData.value);
        feedback.msgSuccess("创建成功");
        close();
        emit("success");
    } catch (error) {
        feedback.msgError(error);
    }
};

const { lockFn, isLock } = useLockFn(handleCreate);

defineExpose({
    setAssetData: (data: any) => {
        const { attachment_type, attachment_content } = data;
        if (attachment_type == MaterialTypeEnum.IMAGE) {
            assetData.image = attachment_content;
        } else if (attachment_type == MaterialTypeEnum.VIDEO) {
            assetData.video = attachment_content;
        } else if (attachment_type == MaterialTypeEnum.LINK) {
            assetData.link = attachment_content;
        } else if (attachment_type == MaterialTypeEnum.MINI_PROGRAM) {
            assetData.mini_program = attachment_content;
        }
    },
});
</script>

<style scoped lang="scss">
/* 上传选择区域样式 */
.upload-select {
    @apply w-[216px] py-4 bg-[#F7F7F7] flex flex-col items-center justify-center rounded-lg border border-[#A8A8A8] border-dashed hover:border-primary relative;
    transition: border-color 0.3s ease;
}

/* 文本域样式调整 */
:deep(.el-textarea__inner) {
    padding-bottom: 50px;

    &::-webkit-scrollbar {
        width: 0;
    }
}

/* 单选按钮间距调整 */
:deep(.el-radio) {
    margin-right: 20px;
}

/* 图片上传组件样式 */
:deep(.img-upload) {
    .el-upload {
        width: 100% !important;
        height: 100% !important;
    }
}
</style>
