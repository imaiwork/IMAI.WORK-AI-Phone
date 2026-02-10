<template>
    <div class="h-full bg-slate-50 w-full overflow-hidden flex flex-col">
        <div class="bg-white h-[72px] flex items-center justify-between px-8 border-b border-[#F1F5F9] flex-shrink-0">
            <div class="flex items-center">
                <div class="w-10 h-10 flex items-center justify-center rounded-xl bg-[#0065fb]/10 text-primary mr-3">
                    <Icon name="el-icon-Promotion" :size="20" />
                </div>
                <div>
                    <div class="text-[18px] text-[#1E293B] font-black tracking-tight">发布朋友圈</div>
                    <div class="text-[10px] text-[#94A3B8] font-medium uppercase tracking-widest">
                        Create New Moment
                    </div>
                </div>
            </div>
            <div v-if="showCloseBtn" class="w-8 h-8" @click="emit('close')">
                <close-btn />
            </div>
        </div>

        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="p-3">
                    <ElForm
                        ref="formRef"
                        :model="formData"
                        :rules="!!formData.id ? {} : rules"
                        label-position="top"
                        :disabled="!!formData.id">
                        <div class="space-y-3">
                            <div class="form-section-card">
                                <div class="section-header">
                                    <div class="section-title-tag"></div>
                                    <span class="text-[15px] font-black text-[#1E293B]">基础文本内容</span>
                                </div>

                                <div class="mt-4 relative group">
                                    <ElInput
                                        ref="contentRef"
                                        v-model="formData.content"
                                        type="textarea"
                                        resize="none"
                                        placeholder="分享此刻的想法..."
                                        :autosize="{ minRows: 5, maxRows: 12 }"
                                        maxlength="300"
                                        class="custom-textarea" />

                                    <div class="absolute bottom-2 left-2">
                                        <ElPopover
                                            placement="top-start"
                                            width="400"
                                            trigger="click"
                                            :show-arrow="false"
                                            :popper-style="{ padding: 0 }">
                                            <template #reference>
                                                <div class="emoji-trigger">
                                                    <Icon name="local-icon-phiz" :size="20" />
                                                </div>
                                            </template>
                                            <EmojiContainer @chooseEmoji="handleChooseEmoji" />
                                        </ElPopover>
                                    </div>
                                    <div class="absolute bottom-3 right-4 text-[11px] text-[#94A3B8] font-medium">
                                        {{ formData.content?.length || 0 }}/300
                                    </div>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <div class="section-header justify-between">
                                    <div class="flex items-center">
                                        <div class="section-title-tag"></div>
                                        <span class="text-[15px] font-black text-[#1E293B]">媒体素材附件</span>
                                    </div>
                                    <div
                                        class="text-[11px] text-primary bg-primary/5 px-2 py-0.5 rounded-md font-medium uppercase">
                                        {{ postTypeList.find((i) => i.value === formData.attachment_type)?.label }}
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div class="flex flex-wrap gap-2 mb-6" v-if="!formData.id">
                                        <div
                                            v-for="item in postTypeList"
                                            :key="item.value"
                                            @click="formData.attachment_type = item.value"
                                            :class="[
                                                'type-tab',
                                                formData.attachment_type === item.value ? 'active' : '',
                                            ]">
                                            {{ item.label.replace("附加", "") }}
                                        </div>
                                    </div>

                                    <div class="media-container">
                                        <template v-if="formData.attachment_type === MaterialTypeEnum.IMAGE">
                                            <div class="grid grid-cols-3 gap-3">
                                                <div
                                                    v-for="(item, index) in assetData.image"
                                                    :key="index"
                                                    class="asset-item group">
                                                    <ElImage
                                                        :src="item"
                                                        :preview-src-list="assetData.image"
                                                        :initial-index="index"
                                                        class="w-full h-full rounded-xl overflow-hidden"
                                                        fit="cover" />
                                                    <div
                                                        v-if="!formData.id"
                                                        class="remove-btn"
                                                        @click="handleRemoveImage(index)">
                                                        <Icon name="el-icon-Close" :size="10" />
                                                    </div>
                                                </div>
                                                <upload
                                                    v-if="imageUploadLimit > 0 && !formData.id"
                                                    class="asset-upload-btn w-full"
                                                    type="image"
                                                    show-progress
                                                    :limit="imageUploadLimit"
                                                    :show-file-list="false"
                                                    @success="uploadSuccess">
                                                    <div
                                                        class="flex flex-col items-center justify-center h-[120px] w-full">
                                                        <Icon
                                                            name="local-icon-image_add"
                                                            :size="24"
                                                            color="var(--color-primary)" />
                                                        <span class="text-[11px] font-medium text-[#64748B] mt-1"
                                                            >添加图片</span
                                                        >
                                                        <span class="text-[#999999] text-[11px]">
                                                            或<a @click.stop="openMaterialPicker" class="text-primary"
                                                                >从素材库选择</a
                                                            >
                                                        </span>
                                                    </div>
                                                </upload>
                                            </div>
                                        </template>

                                        <template v-if="formData.attachment_type === MaterialTypeEnum.VIDEO">
                                            <div
                                                v-if="assetData.video"
                                                class="relative rounded-2xl overflow-hidden border-2 border-white shadow-sm">
                                                <video
                                                    :src="assetData.video"
                                                    class="w-full aspect-video object-cover" />
                                                <div class="remove-btn !top-2 !right-2" @click="handleRemoveVideo">
                                                    <Icon name="el-icon-Close" :size="12" />
                                                </div>
                                            </div>
                                            <upload
                                                v-else-if="!formData.id"
                                                class="w-full"
                                                type="video"
                                                :limit="1"
                                                :show-file-list="false"
                                                show-progress
                                                @success="uploadSuccess">
                                                <div class="media-empty-state">
                                                    <Icon
                                                        name="local-icon-video_add"
                                                        :size="32"
                                                        color="var(--color-primary)" />
                                                    <span class="text-[13px] font-medium mt-2"
                                                        ><a @click.stop="openMaterialPicker" class="text-primary"
                                                            >点击上传</a
                                                        >或从素材库选择视频</span
                                                    >
                                                </div>
                                            </upload>
                                        </template>

                                        <template v-if="isLinkOrMiniProgram">
                                            <div v-if="hasLinkOrMiniProgramAsset" class="relative group">
                                                <div class="p-1 bg-white rounded-xl shadow-sm border border-[#F1F5F9]">
                                                    <link-card
                                                        v-if="formData.attachment_type === MaterialTypeEnum.LINK"
                                                        v-bind="assetData.link" />
                                                    <mini-program-card
                                                        v-else-if="
                                                            formData.attachment_type === MaterialTypeEnum.MINI_PROGRAM
                                                        "
                                                        v-bind="assetData.mini_program" />
                                                </div>
                                                <div class="remove-btn" @click="handleRemoveLinkOrMiniProgram">
                                                    <Icon name="el-icon-Close" :size="12" />
                                                </div>
                                            </div>
                                            <div v-else class="media-empty-state" @click="openMaterialPicker">
                                                <Icon name="local-icon-file2" :size="32" color="var(--color-primary)" />
                                                <span class="text-[13px] font-medium mt-2"
                                                    >点击从素材库选择链接信息</span
                                                >
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section-card">
                                <div class="section-header">
                                    <div class="section-title-tag"></div>
                                    <span class="text-[15px] font-black text-[#1E293B]">发送执行策略</span>
                                </div>
                                <div class="mt-4 flex flex-col gap-4">
                                    <!-- <ElRadioGroup v-model="formData.task_type" class="custom-radio-group">
                                        <ElRadio :value="0" border class="strategy-radio">立即发送</ElRadio>
                                        <ElRadio :value="1" border class="strategy-radio">定时发送</ElRadio>
                                    </ElRadioGroup> -->
                                    <ElDatePicker
                                        v-model="formData.date"
                                        type="date"
                                        :disabled="!!formData.id"
                                        :disabled-date="getDisabledDate"
                                        value-format="YYYY-MM-DD"
                                        placeholder="请选择确切的发送时间"
                                        class="!w-full custom-datepicker" />
                                    <!-- 时间选择 -->
                                    <ElTimePicker
                                        v-model="formData.time_config"
                                        :disabled="!!formData.id"
                                        format="HH:mm"
                                        is-range
                                        value-format="HH:mm"
                                        range-separator="至"
                                        placeholder="请选择确切的发送时间"
                                        class="!w-full custom-timepicker" />
                                </div>
                            </div>

                            <div class="form-section-card" v-if="false">
                                <div class="section-header">
                                    <div class="section-title-tag"></div>
                                    <span class="text-[15px] font-black text-[#1E293B]">附加自动评论</span>
                                </div>
                                <div class="mt-4">
                                    <ElInput
                                        v-model="formData.comment"
                                        type="textarea"
                                        placeholder="输入评论，多个评论请用 ## 隔开"
                                        resize="none"
                                        :rows="3"
                                        class="custom-textarea" />
                                </div>
                            </div>

                            <div v-if="isShowWeChat" class="form-section-card">
                                <div class="section-header">
                                    <div class="section-title-tag"></div>
                                    <span class="text-[15px] font-black text-[#1E293B]">执行发送账号</span>
                                </div>
                                <div class="mt-4">
                                    <ElSelect
                                        v-model="formData.wechat_ids"
                                        multiple
                                        filterable
                                        clearable
                                        placeholder="选择要同步的微信号"
                                        class="!w-full custom-select">
                                        <ElOption
                                            v-for="item in optionsData.wechatLists"
                                            :key="item.account"
                                            :value="item.account"
                                            :label="item.nickname" />
                                    </ElSelect>
                                </div>
                            </div>
                        </div>
                    </ElForm>
                </div>
            </ElScrollbar>
        </div>

        <div v-if="!formData.id" class="p-6 bg-white border-t border-[#F1F5F9]">
            <ElButton
                type="primary"
                class="!w-full !h-[52px] !rounded-[16px] !text-[16px] !font-medium"
                :loading="isLock"
                @click="lockFn">
                立即创建
            </ElButton>
        </div>
    </div>

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

// ** 1. 类型定义与组件接口 **

type FormData = {
    id?: number;
    content: string;
    task_type: 0 | 1;
    attachment_type: MaterialTypeEnum;
    attachment_content: any[];
    comment: string;
    date: string;
    wechat_ids?: string[];
    time_config: string[];
};

const props = withDefaults(
    defineProps<{
        modelValue: FormData;
        isShowWeChat?: boolean;
        showCloseBtn?: boolean;
    }>(),
    {
        isShowWeChat: true,
        showCloseBtn: false,
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: FormData): void;
    (e: "success"): void;
    (e: "close"): void;
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
    date: [
        { required: true, message: "请选择朋友圈发送时间" },
        {
            validator: (rule, value, callback) => {
                if (value && dayjs(value).isBefore(dayjs().add(30, "minutes"))) {
                    callback(new Error("发送时间不能小于当前时间30分钟"));
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
    // { label: "附加链接", value: MaterialTypeEnum.LINK, disabled: false },
    // { label: "附加小程序", value: MaterialTypeEnum.MINI_PROGRAM, disabled: false },
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
const prepareAttachmentContent = (): any[] => {
    const { attachment_type } = formData.value;

    switch (attachment_type) {
        case MaterialTypeEnum.IMAGE:
            return assetData.image;
        case MaterialTypeEnum.VIDEO:
            return [assetData.video];
        // case MaterialTypeEnum.LINK:
        //     return assetData.link;
        // case MaterialTypeEnum.MINI_PROGRAM:
        //     return assetData.mini_program;
        default:
            return [];
    }
};

/**
 * 处理表单提交
 */
const handleCreate = async () => {
    // 设置附件内容
    formData.value.attachment_content = prepareAttachmentContent();
    // 表单验证
    await formRef.value?.validate();

    if (!formData.value.content) {
        feedback.msgWarning("请输入朋友圈基础文本内容");
        return;
    }
    if (formData.value.attachment_content.length == 0) {
        feedback.msgWarning("请上传图片或视频");
        return;
    }
    if (formData.value.time_config.length === 2) {
        const [start, end] = formData.value.time_config;
        if (dayjs(end, "HH:mm").diff(dayjs(start, "HH:mm"), "minutes") < 30) {
            feedback.msgWarning("时间间隔不能小于30分钟");
            return;
        }
    }
    if (formData.value.wechat_ids.length == 0) {
        feedback.msgWarning("请选择发送账号");
        return;
    }
    try {
        // 判断附加内容是不是为空
        if (!formData.value.attachment_content) {
            formData.value.attachment_type = MaterialTypeEnum.TEXT;
        }

        // 如果是立即发送，清空发送时间
        if (formData.value.task_type === 0) {
            formData.value.date = "";
        }

        // 提交表单
        await circleTaskAdd({
            ...formData.value,
            time_config: `${formData.value.time_config[0]}-${formData.value.time_config[1]}`,
        });
        feedback.msgSuccess("创建成功");
        emit("close");
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
.form-section-card {
    @apply bg-white rounded-[24px] p-5  border border-[#F1F5F9];
}

.section-header {
    @apply flex items-center mb-1;
    .section-title-tag {
        @apply w-[4px] h-[16px] bg-primary rounded-full mr-3;
    }
}

.type-tab {
    @apply px-4 py-2 bg-slate-100 text-[#64748B] rounded-xl text-xs font-medium cursor-pointer transition-all border border-[transparent];
    &:hover {
        @apply bg-slate-200;
    }
    &.active {
        @apply bg-[#0065fb]/10 text-primary border-[#0065fb]/20;
    }
}

/* 媒体展示 */
.media-container {
    @apply min-h-[120px];
}

.asset-item {
    @apply relative aspect-square bg-slate-50 rounded-xl border border-gray-100 leading-[0];
}

.asset-upload-btn {
    @apply aspect-square bg-slate-50 rounded-[20px] border-2 border-dashed border-slate-200 hover:border-primary hover:bg-[#0065fb]/5 transition-all cursor-pointer;
}

.media-empty-state {
    @apply w-full py-10 flex flex-col items-center justify-center bg-slate-50 rounded-[20px] border-2 border-dashed border-slate-200 text-[#94A3B8] cursor-pointer hover:border-primary transition-all;
}

.remove-btn {
    @apply absolute -top-1.5 -right-1.5 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center cursor-pointer shadow-light hover:scale-110 transition-transform z-10;
}

.custom-radio-group {
    @apply w-full flex gap-3;
    .strategy-radio {
        @apply flex-1 m-0 h-11 rounded-xl px-4 flex items-center justify-center font-medium;
        &.is-checked {
            @apply bg-[#0065fb]/5 border-[#0065fb]/30;
        }
    }
}

.emoji-trigger {
    @apply w-10 h-10 flex items-center justify-center bg-white text-[#94A3B8] rounded-xl shadow-light hover:text-primary hover:scale-110 transition-all cursor-pointer;
}

.custom-datepicker :deep(.el-input__wrapper) {
    @apply rounded-xl bg-slate-50 border-[none] shadow-[none] py-2;
}

.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
