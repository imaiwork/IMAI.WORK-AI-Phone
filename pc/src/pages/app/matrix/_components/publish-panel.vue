<template>
    <div class="h-full bg-app-bg-2 rounded-[20px] overflow-x-auto dynamic-scroller">
        <div class="h-full flex flex-col min-w-[1000px]">
            <!-- 头部导航 -->
            <div class="flex-shrink-0 flex items-center justify-between px-[14px] h-[88px] border-b border-[#ffffff1a]">
                <div class="flex items-center gap-2 cursor-pointer" @click="handleBack">
                    <Icon name="el-icon-ArrowLeft" color="#ffffff"></Icon>
                    <div class="text-white">返回上一步</div>
                </div>
                <div class="flex items-center gap-1">
                    <ElButton
                        class="!rounded-full !h-10 w-[98px] !border-app-border-2"
                        color="#181818"
                        :disabled="isSubmitting"
                        @click="handleCancel"
                        >取消</ElButton
                    >
                    <ElButton
                        type="primary"
                        class="!rounded-full !h-10 w-[98px]"
                        :loading="isSubmitting"
                        @click="handleNext">
                        {{ isLastStep ? "提交" : "下一步" }}
                    </ElButton>
                </div>
            </div>
            <!-- 步骤条 -->
            <div class="px-5">
                <div
                    class="flex-shrink-0 flex items-center justify-center h-[100px] border-b border-[#ffffff1a] gap-x-[150px]">
                    <div v-for="(item, index) in steps" :key="index" class="relative">
                        <div class="flex flex-col items-center gap-2 cursor-pointer" @click="handleStep(item.step)">
                            <div
                                class="w-6 h-6 rounded-full flex items-center justify-center text-white"
                                :class="[step >= item.step ? 'bg-primary' : 'bg-[#ffffff0d]']">
                                {{ index + 1 }}
                            </div>
                            <div :class="[step >= item.step ? 'text-white' : 'text-[#ffffff80]']">
                                {{ item.title }}
                            </div>
                        </div>
                        <div
                            class="absolute w-[120px] h-[1px] left-[calc(100%+15px)] top-[10px]"
                            :class="[step > item.step ? 'bg-primary' : 'bg-[#ffffff1a]']"
                            v-if="index != steps.length - 1"></div>
                    </div>
                </div>
            </div>
            <!-- 内容区域 -->
            <div class="grow min-h-0 flex flex-col p-5">
                <!-- 步骤一：基本信息 -->
                <div class="grow min-h-0 flex flex-col w-[456px] mx-auto" v-if="step == 1">
                    <div class="flex items-center justify-between flex-shrink-0">
                        <div class="text-white font-bold text-[20px]">选择{{ publishTypeMap[type] }}</div>
                    </div>
                    <div class="grow min-h-0 mt-5 flex gap-x-[18px]">
                        <!-- 素材区 -->
                        <div class="content-item material-content">
                            <div class="flex items-center justify-between flex-shrink-0 px-[14px]">
                                <template v-if="isVideoMode">
                                    <div class="text-[11px] text-white">
                                        视频（共{{ materialFormData.media_url.length }}个视频）
                                    </div>
                                    <div class="text-[11px] text-[#ffffff80] mt-2">
                                        最多可选{{ videoLimit }}个，视频{{ videoSize }}m以下
                                    </div>
                                </template>
                                <template v-if="isImageMode">
                                    <div>
                                        <div class="text-[11px] text-white">
                                            图文（共{{ materialFormData.media_url.length }}组）
                                        </div>
                                        <div class="text-[11px] text-[#ffffff80] mt-2">
                                            每组图片不超过{{ maxImageCount }}张
                                        </div>
                                    </div>
                                    <ElButton
                                        class="!h-10 w-[106px] !border-[#ffffff1a]"
                                        color="#262626"
                                        @click="handleAddImageGroup"
                                        >添加图片组</ElButton
                                    >
                                </template>
                            </div>
                            <div class="grow min-h-0">
                                <ElScrollbar>
                                    <div class="px-[14px]">
                                        <div
                                            class="mt-[14px] border border-app-border-2 rounded-xl p-2"
                                            v-if="isVideoMode">
                                            <MaterialPicker
                                                v-model:material-list="materialFormData.media_url"
                                                :type="type"
                                                :accept="videoFormat"
                                                :max-video-count="videoLimit"
                                                :max-size="videoSize"
                                                @preview-video="handlePreviewVideo"
                                                @update:material-list="handleUpdateMaterialList"
                                                @import-material="handleImportMaterial($event, 0)"
                                                @change-material="handleChangeMaterial" />
                                        </div>
                                        <template v-if="isImageMode">
                                            <div
                                                class="mt-[14px] border border-app-border-2 rounded-xl p-2 relative"
                                                v-for="(item, index) in materialFormData.media_url"
                                                :key="index">
                                                <MaterialPicker
                                                    v-model:material-list="item.url"
                                                    :type="type"
                                                    :max-size="imageSize"
                                                    :max-image-count="maxImageCount"
                                                    @update:material-list="handleUpdateMaterialList"
                                                    @import-material="handleImportMaterial($event, index)"
                                                    @change-material="handleChangeMaterial" />
                                                <div
                                                    class="absolute -top-2 -right-2 w-5 h-5"
                                                    @click="handleDeleteMaterialGroup(index)">
                                                    <close-btn :icon-size="14" :theme="ThemeEnum.DARK"></close-btn>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </ElScrollbar>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 步骤二：选择内容 -->
                <div class="grow min-h-0 flex flex-col" v-else-if="step == 2">
                    <div class="flex items-center justify-between flex-shrink-0">
                        <div class="text-white font-bold text-[20px]">填写文案</div>
                        <div>
                            <ElButton
                                type="primary"
                                class="!rounded-full !h-10 w-[106px]"
                                @click="openCopywritingMaterial"
                                >智能文案</ElButton
                            >
                        </div>
                    </div>
                    <div class="grow min-h-0 mt-5 flex gap-x-[18px]">
                        <!-- 标题与描述 -->
                        <div class="content-item title-content">
                            <copywriting-card
                                v-model:model-value="materialFormData.title"
                                :type="1"
                                :publish-type-name="publishTypeMap[type]"
                                @update:model-value="updateCopywritingMaterial(materialFormData)" />
                        </div>
                        <div class="content-item desc-content">
                            <copywriting-card
                                v-model:model-value="materialFormData.subtitle"
                                :type="2"
                                :publish-type-name="publishTypeMap[type]"
                                @update:model-value="updateCopywritingMaterial(materialFormData)" />
                        </div>
                    </div>
                </div>
                <!-- 步骤三：发布设置 -->
                <ElScrollbar v-else-if="step == 3">
                    <div class="w-[456px] mx-auto">
                        <div class="font-bold text-white text-[20px]">发布设置</div>
                        <div class="mt-[23px] flex flex-col gap-y-4">
                            <!-- 账号选择 -->
                            <div>
                                <div class="text-[#ffffff80] mb-3">账号选择</div>
                                <ElSelect
                                    v-model="formData.accounts"
                                    placeholder="请选择发布的账号"
                                    class="!w-full !h-11 account-select"
                                    popper-class="dark-select-popper"
                                    multiple
                                    collapse-tags
                                    :show-arrow="false">
                                    <ElOption
                                        v-for="item in accountList"
                                        :key="item.account"
                                        :label="`${item.nickname}（${item.account}）`"
                                        :value="item.account">
                                        <div class="flex items-center gap-x-2">
                                            <img :src="getPlatform(item.type)?.icon" class="w-4 h-4" />
                                            <div>{{ item.nickname }}（{{ item.account }}）</div>
                                        </div>
                                    </ElOption>
                                </ElSelect>
                            </div>
                            <!-- 发布频率 -->
                            <div>
                                <div class="text-[#ffffff80]">发布频率（每日）</div>
                                <div class="flex flex-wrap gap-2 mt-4">
                                    <div
                                        v-for="(item, index) in [1, 2, 3, 5, 10]"
                                        :key="index"
                                        class="cursor-pointer rounded-md px-4 py-2 border border-app-border-2 text-white hover:bg-app-bg-1"
                                        :class="[formData.publish_frep == item ? 'bg-primary hover:bg-primary' : '']"
                                        @click="handleFrequency(item)">
                                        {{ item }}条
                                    </div>
                                </div>
                            </div>
                            <!-- 发布时间 -->
                            <div>
                                <div class="text-[#ffffff80] mb-3">选择时间</div>
                                <div class="mt-4">
                                    <!-- 随机发布 -->
                                    <div class="rounded-xl bg-app-bg-3 border border-app-border-2 py-3">
                                        <div class="px-3">
                                            <ElAlert type="warning" effect="dark"
                                                ><span class="text-xs"
                                                    >设置的时间必须为n+1组的时间大于n组的时间，时间间隔{{
                                                        publishTimeGapMin
                                                    }}分钟</span
                                                ></ElAlert
                                            >
                                            <div class="text-white mt-4">任务发布设置</div>
                                        </div>
                                        <ElScrollbar ref="timeConfigScrollbarRef">
                                            <div
                                                class="flex flex-col gap-3 mt-[5px] max-h-[300px] p-3"
                                                ref="timeConfigWrapperRef">
                                                <div v-for="(, index) in formData.time_config">
                                                    <div class="text-[#ffffff80] mb-[13px] text-xs">
                                                        每天第{{ index + 1 }}个任务发布时间
                                                    </div>
                                                    <ElTimePicker
                                                        class="w-full"
                                                        :class="{ 'time-error': timeErrorIndex.includes(index) }"
                                                        v-model="formData.time_config[index]"
                                                        is-range
                                                        range-separator="至"
                                                        prefix-icon=""
                                                        format="HH:mm"
                                                        value-format="HH:mm"
                                                        popper-class="dark-select-popper"
                                                        :show-arrow="false" />
                                                </div>
                                            </div>
                                        </ElScrollbar>
                                    </div>
                                    <div v-if="taskErrorMsg" class="mt-2">
                                        <div>任务冲突</div>
                                        <view class="text-[#FF2442] mt-1 text-xs">
                                            {{ taskErrorMsg }}
                                        </view>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
        </div>
    </div>
    <!-- 弹窗 -->
    <content-copywriting-material
        v-if="showCopywritingMaterial"
        ref="copywritingMaterialRef"
        @close="showCopywritingMaterial = false"
        @confirm="handleChooseCopywriting" />
    <material-popup
        v-if="showMaterialPop"
        ref="materialPopRef"
        :type="materialTypeMap[type]"
        :limit="materialPickerLimit"
        :multiple="isBatchPickingMaterial"
        @close="showMaterialPop = false"
        @confirm="handleChooseMaterial" />
    <preview-video v-if="showPreviewVideo" ref="previewVideoRef" @close="showPreviewVideo = false"></preview-video>
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { ElScrollbar } from "element-plus";
import { getDeviceAccountList as getDeviceAccountListApi, addMatrixTask, publishDeviceTask } from "@/api/device";
import { uploadImage } from "@/api/app";
import { ThemeEnum } from "@/enums/appEnums";
import { PublishTaskTypeEnum, MaterialActionType, MaterialTypeEnum, SidebarTypeEnum } from "../_enums";
import ContentCopywritingMaterial from "./content-copywriting-material.vue";
import CopywritingCard from "./copywriting-card.vue";
import MaterialPicker from "./material-picker.vue";
import MaterialPopup from "./material-popup.vue";
import useMaterial from "../_hooks/useMaterial";
import { validateSchedule } from "./utils";

// =================================================================================================
// Props & Emits
// =================================================================================================

const props = withDefaults(defineProps<{ type: PublishTaskTypeEnum }>(), {
    type: PublishTaskTypeEnum.VIDEO,
});

const emit = defineEmits<{ (e: "back"): void }>();

// =================================================================================================
// Composable & Utils
// =================================================================================================

const route = useRoute();
const router = useRouter();
const nuxtApp = useNuxtApp();
const { updateCopywritingMaterial } = useMaterial();
const { getPlatform } = useSocialPlatform();

// =================================================================================================
// Enums & Constants
// =================================================================================================

const publishTypeMap = {
    [PublishTaskTypeEnum.VIDEO]: "视频",
    [PublishTaskTypeEnum.IMAGE]: "图文",
};

const materialTypeMap = {
    [PublishTaskTypeEnum.VIDEO]: MaterialTypeEnum.VIDEO,
    [PublishTaskTypeEnum.IMAGE]: MaterialTypeEnum.IMAGE,
};

const steps = [
    { step: 1, title: "选择素材" },
    { step: 2, title: "填写文案" },
    { step: 3, title: "设定时间" },
];

// 视频上传限制
const videoLimit = 99;
// 视频上传大小
const videoSize = 100;
// 视频上传格式
const videoFormat = ".mp4,.mov";
// 图片上传大小
const imageSize = 50;

const maxImageCount = 9;
const publishTimeGapMin = 30;

// =================================================================================================
// State
// =================================================================================================

const { type } = toRefs(props);
const query = searchQueryToObject();
const materialId = computed(() => query.material_id);

const step = ref(1);

// 表单数据
const formData = reactive({
    name: `${publishTypeMap[type.value]}矩阵任务${dayjs().format("YYYYMMDDHHmmss")}`,
    accounts: [],
    media_type: type.value,
    time_config: [],
    publish_frep: 2,
});

// 素材数据
const materialFormData = reactive({
    id: materialId.value,
    name: formData.name,
    media_type: type.value,
    media_url: [],
    title: [] as Array<{ content: string }>,
    subtitle: [] as Array<{ content: string; topic: string[] }>,
});

// 弹窗显示状态
const showCopywritingMaterial = ref(false);
const showMaterialPop = ref(false);
const showPreviewVideo = ref(false);

// Refs
const copywritingMaterialRef = ref();
const materialPopRef = shallowRef<InstanceType<typeof MaterialPopup>>();
const previewVideoRef = ref();
const timeConfigScrollbarRef = ref<InstanceType<typeof ElScrollbar>>();
const timeConfigWrapperRef = ref<HTMLDivElement>();

const accountList = ref([]);

// 错误状态
const timeErrorIndex = ref([]);
//错误提示
const taskErrorMsg = ref<string>("");

// =================================================================================================
// Computed
// =================================================================================================

const isLastStep = computed(() => step.value === steps.length);
const isVideoMode = computed(() => type.value === PublishTaskTypeEnum.VIDEO);
const isImageMode = computed(() => type.value === PublishTaskTypeEnum.IMAGE);

// =================================================================================================
// Navigation
// =================================================================================================

const handleBack = () => {
    if (step.value === 1) {
        closePanel();
    } else {
        step.value--;
    }
};

const handleStep = (stepValue: number) => {
    if (stepValue > step.value) {
        handleNext();
    } else {
        step.value = stepValue;
    }
};

const handleNext = async () => {
    let success = false;
    if (step.value === 1) {
        success = await submitStep1();
    } else if (step.value === 2) {
        success = await submitStep2();
    } else if (isLastStep.value) {
        await submitForm();
        return; // 提交后直接返回，不进入下一步
    }

    if (success) {
        step.value++;
        replaceState({ ...route.query, step: step.value });
    }
};

const handleCancel = () => {
    nuxtApp.$confirm({
        message: "确定要取消创建吗？",
        theme: "dark",
        onConfirm: closePanel,
    });
};

const closePanel = () => {
    emit("back");
    step.value = 1;
};

// =================================================================================================
// Step 1: 基本信息
// =================================================================================================

const submitStep1 = async () => {
    if (materialFormData.media_url.length === 0) {
        feedback.msgWarning("请选择素材");
        return false;
    }
    if (type.value === PublishTaskTypeEnum.IMAGE) {
        // 检查每一个图片组是否都有效
        if (
            materialFormData.media_url.some(
                (item) => !Array.isArray(item.url) || item.url.length === 0 || item.url.some((url) => !url)
            )
        ) {
            feedback.msgWarning("请为每个图片组添加有效的图片，或删除空的图片组");
            return false;
        }
    }

    return true;
};

// =================================================================================================
// Step 2: 文案选择
// =================================================================================================

const submitStep2 = async () => {
    if (materialFormData.title.length === 0 || materialFormData.title.every((item) => !item.content)) {
        feedback.msgWarning("请填写标题");
        return false;
    }

    if (materialFormData.subtitle.length === 0 || materialFormData.subtitle.every((item) => !item.content)) {
        feedback.msgWarning("请填写描述");
        return false;
    }
    initPublishJsonForStep3();
    return true;
};

// --- 素材选择 ---
const currMaterialGroupIndex = ref(0);
const currMaterialIndex = ref(-1); // -1 表示批量添加, >-1 表示替换

const materialPickerLimit = computed(() => {
    if (isVideoMode.value) {
        return currMaterialIndex.value === -1 ? videoLimit : videoLimit - materialFormData.media_url.length;
    }
    if (isImageMode.value) {
        const group = materialFormData.media_url[currMaterialGroupIndex.value];
        return currMaterialIndex.value === -1 ? maxImageCount : maxImageCount - (group?.url.length || 0);
    }
    return 0;
});

const isBatchPickingMaterial = computed(() => currMaterialIndex.value === -1);

const handleImportMaterial = async (event: any, groupIndex: number) => {
    currMaterialGroupIndex.value = groupIndex;
    currMaterialIndex.value = event.index > -1 ? event.index : -1;
    showMaterialPop.value = true;
    await nextTick();
    materialPopRef.value?.open();
};

const handleChooseMaterial = async (lists: any[]) => {
    if (isVideoMode.value) {
        if (isBatchPickingMaterial.value) {
            for (const item of lists) {
                const data = {
                    url: item.url,
                    pic: item.pic,
                };
                if (!item.pic) {
                    try {
                        const { file } = await getVideoFirstFrame(item.url);
                        const res = await uploadImage({ file });
                        data.pic = res.uri;
                    } catch (error) {}
                }

                materialFormData.media_url.push(data);
                addTitleAndDesc(item);
            }
        } else {
            materialFormData.media_url[currMaterialIndex.value] = lists[0];
        }
    } else if (isImageMode.value) {
        const group = materialFormData.media_url[currMaterialGroupIndex.value];
        if (isBatchPickingMaterial.value) {
            group.url.push(...lists);
        } else {
            group.url[currMaterialIndex.value] = lists[0];
        }
    }
    updateCopywritingMaterial(materialFormData);
};

const handleUpdateMaterialList = () => updateCopywritingMaterial(materialFormData);

const handleChangeMaterial = (data: any) => {
    if (data.type === MaterialActionType.ADD && isVideoMode.value) {
        addTitleAndDesc();
    }
};

const handleAddImageGroup = () => {
    materialFormData.media_url.push({ url: [], date: "" });
    addTitleAndDesc();
};

const handleDeleteMaterialGroup = (index: number) => {
    nuxtApp.$confirm({
        message: "确定要删除该素材组吗？",
        theme: "dark",
        onConfirm: () => {
            materialFormData.media_url.splice(index, 1);
            updateCopywritingMaterial(materialFormData);
        },
    });
};

// --- 智能文案 ---
const openCopywritingMaterial = async () => {
    showCopywritingMaterial.value = true;
    await nextTick();
    copywritingMaterialRef.value.open();
};

const handleChooseCopywriting = (data: { titleList: any[]; contentList: any[] }) => {
    const { titleList, contentList } = data;
    if (titleList.length > 0) materialFormData.title.push(...titleList);
    if (contentList.length > 0) materialFormData.subtitle.push(...contentList);
    if (titleList.length > 0 || contentList.length > 0) {
        updateCopywritingMaterial(materialFormData);
    }
};

// --- 标题与描述 ---
const addTitleAndDesc = (isUpdate = true) => {
    materialFormData.title.push({ content: "" });
    // 使用setTimeout确保在不同更新周期中执行，避免冲突
    setTimeout(() => materialFormData.subtitle.push({ content: "", topic: [] }), 100);
    if (isUpdate) updateCopywritingMaterial(materialFormData);
};

// =================================================================================================
// Step 3: 时间设置
// =================================================================================================

const validatePublishSettings = () => {
    if (!validateTimeConfig()) return false;

    if (formData.accounts.length === 0) {
        feedback.msgWarning("请选择发布账号");
        return false;
    }

    return true;
};

const validateTimeConfig = () => {
    const { valid, errorIndexes } = validateSchedule(formData.time_config);

    if (!valid) {
        timeErrorIndex.value = errorIndexes;
        feedback.msgWarning("时间配置存在冲突");
        return false;
    }
    timeErrorIndex.value = [];
    return true;
};

const handleFrequency = (item: number) => {
    if (item == formData.publish_frep) return;
    formData.publish_frep = item;
    formData.time_config = Array.from({ length: item }, (_, index) => {
        const baseTime = dayjs().hour(9).minute(0);
        const startTime = baseTime.add(index * publishTimeGapMin, "minutes").format("HH:mm");
        const endTime = baseTime.add(index * publishTimeGapMin + publishTimeGapMin, "minutes").format("HH:mm");
        return [startTime, endTime];
    });
};

const { lockFn: submitForm, isLock: isSubmitting } = useLockFn(async () => {
    if (!validatePublishSettings()) return;

    try {
        const copywriterList = materialFormData.title
            .filter((item) => item.content)
            .map((item, index) => {
                const content = materialFormData.subtitle[index]?.content;
                const topic = materialFormData.subtitle[index]?.topic;
                return {
                    title: item.content,
                    content: content,
                    topic: topic,
                };
            });
        let media_url = [];
        if (type.value === PublishTaskTypeEnum.VIDEO) {
            media_url = materialFormData.media_url.map((item) => ({
                url: [item.pic, item.url],
            }));
        } else {
            media_url = materialFormData.media_url.map((item) => ({
                url: item.url.map((val) => val.url),
            }));
        }
        const { id } = await addMatrixTask({
            name: formData.name,
            media_url,
            copywriting: copywriterList,
            media_type: type.value,
        });
        const accountIds = accountList.value
            .filter((item) => formData.accounts.includes(item.account))
            .map((item) => ({ account: item.account, id: item.id, type: item.type }));
        await publishDeviceTask({
            name: formData.name,
            matrix_media_setting_id: id,
            time_config: formData.time_config.map((item) => `${item[0]}-${item[1]}`),
            accounts: accountIds,
            publish_frep: formData.publish_frep,
            media_type: type.value,
            task_type: 3,
            scene: 2,
            data_type: 0,
        });
        feedback.msgSuccess("发布成功");
        router.push(`/app/matrix?type=${SidebarTypeEnum.ME_PUBLISH}`);
        setTimeout(() => {
            window.location.reload();
        }, 500);
    } catch (error) {
        taskErrorMsg.value = error;
        feedback.msgError(error);
    }
});

const handleChangePublishTime = () => {
    if (formData.time_config.length === 0) {
        formData.time_config.push(["09:00", "09:30"]);
        formData.time_config.push(["09:30", "10:00"]);
    }
};

const initPublishJsonForStep3 = () => {
    getAccountList();
    handleChangePublishTime();
};

// =================================================================================================
// Other Actions
// =================================================================================================

const getAccountList = async () => {
    const { lists } = await getDeviceAccountListApi({ page_size: 999 });
    accountList.value = lists;
};

const handlePreviewVideo = async (url: string) => {
    showPreviewVideo.value = true;
    await nextTick();
    previewVideoRef.value?.open();
    previewVideoRef.value?.setUrl(url);
};
</script>

<style scoped lang="scss">
.content-item {
    @apply rounded-xl bg-app-bg-3 py-[14px] border border-app-border-1 flex flex-col grow min-h-0 flex-1;

    &.title-content,
    &.desc-content {
        :deep(.el-input__wrapper) {
            background-color: transparent;
            box-shadow: none;
        }
    }
    :deep(.el-input__inner::placeholder) {
        font-size: 10px;
    }
}
.time-select-wrapper {
    @apply flex items-center gap-x-2 bg-app-bg-3 rounded-md px-2 border border-app-border-1;
    :deep(.el-select .el-select__wrapper) {
        padding: 0;
        box-shadow: none;
    }
}
.account-select.el-select :deep(.el-select__wrapper) {
    border-radius: var(--el-input-border-radius, var(--el-border-radius-base));
}
:deep() {
    .el-date-editor.el-input__wrapper.time-error {
        box-shadow: 0 0 0 1px var(--el-color-error);
    }
}
</style>
