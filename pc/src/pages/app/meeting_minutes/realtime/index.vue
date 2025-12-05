<template>
    <div class="real-time-page">
        <ElTooltip content="返回">
            <div class="absolute top-4 left-6 z-30">
                <ElButton circle @click="back">
                    <Icon name="el-icon-Back" />
                </ElButton>
            </div>
        </ElTooltip>
        <div class="w-[852px] mx-auto">
            <div>
                <div class="text-[32px] font-bold">会议实时记录转写</div>
                <div class="mt-3">语音转文字·发言人区分·智能总结</div>
                <div
                    ref="recorderContainerRef"
                    class="mt-8 bg-white rounded-xl px-[60px] py-12 shadow-[2px_6px_20px_#0000001C]">
                    <template v-if="nextStep == 1">
                        <div class="flex flex-col gap-6">
                            <div class="flex items-center">
                                <div class="w-[132px] text-[#737373]">音频文件的语音</div>
                                <div>
                                    <div class="flex flex-wrap gap-4">
                                        <div
                                            v-for="(item, index) in languageList"
                                            :key="index"
                                            class="bg-[#F5F5F5] rounded-lg px-6 py-1.5 cursor-pointer"
                                            :class="{
                                                'bg-[#f1f6ff] text-primary': formData.language === item.code,
                                            }"
                                            @click="formData.language = item.code">
                                            {{ item.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="w-[132px] text-[#737373]">翻译的目标语言</div>
                                <div class="flex-1">
                                    <ElSelect v-model="formData.translation" placeholder="请选择">
                                        <ElOption
                                            v-for="(item, index) in targetLanguageList"
                                            :key="index"
                                            :label="item.name"
                                            :value="item.code"></ElOption>
                                    </ElSelect>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="w-[132px] text-[#737373]">区分发言人</div>
                                <div>
                                    <div class="flex flex-wrap gap-4">
                                        <div
                                            v-for="(item, index) in speakerOptions"
                                            :key="index"
                                            class="bg-[#F5F5F5] rounded-lg px-[47px] py-1.5 cursor-pointer"
                                            :class="{
                                                'bg-primary-light-9 text-primary': formData.speaker === item.value,
                                            }"
                                            @click="formData.speaker = item.value">
                                            {{ item.label }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 flex justify-center">
                            <ElTooltip>
                                <ElButton
                                    type="primary"
                                    class="!h-[48px] w-[192px] !rounded-full gap-2"
                                    @click="handleStartRecord">
                                    <span class="mr-2"> 开始录音 </span>
                                    <Icon name="el-icon-Warning"></Icon>
                                </ElButton>
                                <template #content>
                                    <div>
                                        <div>
                                            {{ tokensValue.score }}
                                            {{ tokensValue.unit }}
                                        </div>
                                    </div>
                                </template>
                            </ElTooltip>
                        </div>
                        <div class="flex items-center justify-center gap-x-1 mt-4">
                            <Icon name="el-icon-Warning"></Icon>
                            <span class="text-xs">
                                您的算力目前可以转写{{ userRecordTimeLimit }}分钟，算力不足时将终止录音</span
                            >
                        </div>
                    </template>
                    <div v-if="nextStep == 2" class="bg-[#f7f8fc] py-6 px-4 rounded-lg">
                        <RecorderControl
                            ref="recorderControlRef"
                            :disabled="isRecorderDisabled"
                            :is-error="isCreateError"
                            @change="getAudio" />
                        <div class="mt-8 flex justify-center" v-if="isCreateError">
                            <ElButton
                                type="primary"
                                class="!h-[48px] w-[192px] !rounded-full"
                                :loading="isLock"
                                @click="lockCreateTask"
                                >重新上传</ElButton
                            >
                            <ElButton type="danger" class="!h-[48px] w-[192px] !rounded-full" @click="reloadRecord"
                                >重新录音</ElButton
                            >
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-12">
                <div class="font-bold text-lg">最近记录</div>
                <div class="mt-4">
                    <template v-if="!pager.loading">
                        <div class="grid grid-cols-3 gap-4" v-if="pager.lists.length">
                            <div
                                class="bg-white rounded-xl p-4 h-[127px] flex flex-col justify-between relative group overflow-hidden"
                                v-for="(item, index) in pager.lists"
                                :key="index">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex-1 overflow-hidden text-ellipsis whitespace-nowrap">
                                        {{ formatName(item.name) }}
                                    </div>
                                    <img src="../_assets/images/loader.png" class="w-6 h-6 flex-shrink-0" />
                                </div>
                                <div class="flex justify-between items-center text-xs text-[#A5A7B9]">
                                    <div>
                                        <template v-if="item.status == TurnStatus.SUCCESS">
                                            {{ item.task_type == 1 ? "音频记录" : "实时记录" }}
                                        </template>
                                        <template v-else-if="item.status == TurnStatus.ING"> 转写中 </template>
                                        <template v-else-if="item.status == TurnStatus.ERROR"> 转写失败 </template>
                                    </div>
                                    <div>
                                        {{ dayjs(item.create_time).format("MM/DD HH:mm") }}
                                    </div>
                                </div>
                                <div
                                    class="absolute bottom-0 left-0 w-full h-[36px] flex justify-center items-center bg-white z-50 translate-y-full group-hover:translate-y-0 transition-all duration-200">
                                    <div>
                                        <ElPopover
                                            placement="top-start"
                                            :show-arrow="false"
                                            popper-class="!w-[120px] !min-w-[120px] !p-[6px] !rounded-xl "
                                            :popper-options="{
                                                modifiers: [
                                                    {
                                                        name: 'offset',
                                                        options: {
                                                            offset: [-40, 10],
                                                        },
                                                    },
                                                ],
                                            }">
                                            <template #reference>
                                                <div class="leading-[0]">
                                                    <Icon name="el-icon-MoreFilled" color="#8f91a8" :size="12"></Icon>
                                                </div>
                                            </template>
                                            <div class="flex flex-col gap-2">
                                                <div
                                                    class="px-2 py-1 hover:bg-primary-light-9 rounded-lg"
                                                    v-if="[TurnStatus.ERROR].includes(item.status)">
                                                    <ElButton link :icon="Refresh" @click="handleAgain(item.id)">
                                                        重试
                                                    </ElButton>
                                                </div>
                                                <div class="px-2 py-1 hover:bg-primary-light-9 rounded-lg">
                                                    <ElButton link :icon="Delete" @click="handleDelete(item.id)">
                                                        删除
                                                    </ElButton>
                                                </div>
                                                <div
                                                    class="px-2 py-1 hover:bg-primary-light-9 rounded-lg"
                                                    v-if="item.status == TurnStatus.SUCCESS">
                                                    <ElButton link :icon="DocumentAdd" @click="openKnbBind(item)">
                                                        训练知识库
                                                    </ElButton>
                                                </div>
                                            </div>
                                        </ElPopover>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="py-4">
                            <ElEmpty description="暂无数据"></ElEmpty>
                        </div>
                    </template>
                    <div class="mt-8 flex flex-col items-center" v-else>
                        <Loader />
                        <div class="text-sm text-gray-500 mt-10">加载中...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <KnbBind v-if="showKnbBind" ref="knbBindRef" @close="showKnbBind = false" />
</template>

<script setup lang="ts">
import Recorder from "recorder-core";
import { Delete, Refresh, DocumentAdd } from "@element-plus/icons-vue";
import { meetingMinutesCreate } from "@/api/meeting_minutes";
import { useAppStore } from "@/stores/app";
import { dayjs } from "element-plus";
import RecorderControl from "../detail/_components/recorder-control.vue";
import { TurnStatus } from "../_enums";
import useHandleApi from "../_hooks/useHandleApi";
import KnbBind from "@/components/knb-bind/index.vue";

const router = useRouter();
const nuxtApp = useNuxtApp();
const {
    pager,
    userTokens,
    tokensValue,
    speakerOptions,
    languageList,
    targetLanguageList,
    getLists,
    handleAgain,
    handleDelete,
    handleTrain,
    formatName,
} = useHandleApi();

// 计算当前用户能录音多长时间
const userRecordTimeLimit = computed(() => {
    return Math.floor(userTokens.value / 3);
});

const formData = reactive<any>({
    language: "cn",
    speaker: 0,
    translation: 0,
    task_type: 1,
});

const recorderContainerRef = ref<HTMLDivElement>();
const recorderControlRef = ref<InstanceType<typeof RecorderControl>>();

const nextStep = ref(1);

const recorder = ref<any>(null);
const isCreateError = ref<boolean>(false);
const handleStartRecord = async () => {
    isCreateError.value = false;
    await nextTick();
    recorderControlRef.value?.resetRecord();
    if (tokensValue.value.score <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    recorder.value = Recorder();
    recorder.value.open(
        () => {
            nextStep.value = 2;
        },
        (msg: string, isUserNotAllow: any) => {
            feedback.msgWarning((isUserNotAllow ? "UserNotAllow，" : "") + "无法录音:" + msg);
        }
    );
};

const reloadRecord = async () => {
    await nuxtApp.$confirm({
        message: "确定要重新录音吗？",
        onConfirm: async () => {
            isCreateError.value = false;
            recorderControlRef.value?.resetRecord();
            recorderControlRef.value?.openRecorder();
        },
    });
};

const isRecorderDisabled = computed(() => {
    return isLock.value || isCreateError.value;
});

// 获取录音回调
const getAudio = async (result: any) => {
    const fileName = `${dayjs().format("YYYY-MM-DD HH:mm:ss")} 记录`;
    const { uri } = result;
    formData.url = uri;
    formData.name = fileName;
    feedback.closeLoading();
    lockCreateTask();
};

const showKnbBind = ref(false);
const knbBindRef = ref<InstanceType<typeof KnbBind>>();
const openKnbBind = async (item: any) => {
    handleTrain(item, async (result: any) => {
        showKnbBind.value = true;
        await nextTick();
        knbBindRef.value?.open();
        knbBindRef.value?.setFormData(result);
    });
};

const createTask = async () => {
    feedback.loading("创建中...", recorderContainerRef.value);
    try {
        await meetingMinutesCreate({
            ...formData,
            translation: formData.translation == 0 ? "" : formData.translation,
        });
        feedback.msgSuccess("创建成功,即将返回列表");

        setTimeout(() => {
            router.back();
        }, 1000);
    } catch (error) {
        isCreateError.value = true;
        feedback.msgError(error || "创建失败");
    } finally {
        feedback.closeLoading();
    }
};

const { lockFn: lockCreateTask, isLock } = useLockFn(createTask);

const back = () => {
    if (nextStep.value == 2 && !isCreateError.value) {
        nuxtApp.$confirm({
            message: "确定结束录音吗？结束后无法在本记录继续录音",
            onConfirm: async () => {
                feedback.loading("结束录音中...");
                recorderControlRef.value?.stopRecord();
            },
        });
    } else {
        router.back();
    }
};

getLists();

definePageMeta({
    title: "会议实时记录转写",
    layout: false,
});
</script>

<style scoped lang="scss">
.real-time-page {
    @apply w-full h-full relative overflow-y-auto pt-[100px];

    &::after {
        content: "";
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        background: url("../_assets/images/home_bg.png");
        background-size: 100% auto;
        background-repeat: no-repeat;
        z-index: -1;
    }
}
</style>
