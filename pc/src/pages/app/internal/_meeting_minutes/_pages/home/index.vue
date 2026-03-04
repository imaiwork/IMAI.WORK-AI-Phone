<template>
    <div class="meeting-minutes-page">
        <ElScrollbar>
            <div class="w-[1000px] mx-auto">
                <div class="mt-2">
                    <img src="../../_assets/images/home_txt_title.png" class="h-[150px] object-cover" />
                </div>
                <div class="px-8 mt-10 w-full h-[260px]">
                    <div class="flex items-start gap-x-[30px]">
                        <div
                            v-for="item in mainCardLists"
                            :class="item.class"
                            class="group"
                            @click="handleMainCard(item.class)">
                            <div>
                                <div class="icon"></div>
                                <div class="active-icon"></div>
                            </div>
                            <div class="container pt-4">
                                <div class="font-medium text-[22px] title">
                                    {{ item.title }}
                                </div>
                                <div class="mt-4 leading-6 desc">
                                    <div>{{ item.desc1 }}</div>
                                    <div>{{ item.desc2 }}</div>
                                </div>
                                <div
                                    class="mt-4 font-medium text-[#27264D] group-hover:text-white invisible group-hover:visible">
                                    <Icon name="el-icon-Right" :size="24"></Icon>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-8 mt-10">
                    <div class="flex items-end justify-between mb-6">
                        <div class="flex flex-col gap-1">
                            <div class="text-xl font-[900] text-gray-950 tracking-tight">最近记录</div>
                            <div class="text-xs text-tx-placeholder font-medium">
                                查看及管理您最近的音频转写与实时记录
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <template v-if="!pager.loading || isLoop">
                            <template v-if="pager.lists.length">
                                <div class="rounded-2xl overflow-hidden bg-white">
                                    <ElTable
                                        :data="pager.lists"
                                        :row-style="{ height: '72px', cursor: 'pointer' }"
                                        @row-click="handleItem">
                                        <ElTableColumn prop="name" label="文件名称" min-width="220">
                                            <template #default="{ row }">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        v-if="
                                                            row.status == TurnStatus.ING ||
                                                            row.status == TurnStatus.WAITING
                                                        "
                                                        class="w-10 h-10 rounded-xl bg-primary/5 flex items-center justify-center flex-shrink-0">
                                                        <img
                                                            src="../../_assets/images/audio_transform.gif"
                                                            class="w-7 h-7" />
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center flex-shrink-0">
                                                        <Icon
                                                            :name="
                                                                row.task_type == 1 ? 'el-icon-Headset' : 'el-icon-Mic'
                                                            "
                                                            :size="18"
                                                            color="#64748B" />
                                                    </div>

                                                    <div class="flex flex-col overflow-hidden">
                                                        <span class="text-sm font-black text-gray-950 truncate">{{
                                                            formatName(row.name)
                                                        }}</span>
                                                        <span class="text-[11px] text-tx-placeholder font-medium">{{
                                                            row.task_type == 1 ? "音频素材" : "实时现场"
                                                        }}</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </ElTableColumn>

                                        <ElTableColumn label="摘要/关键词" min-width="240">
                                            <template #default="{ row }">
                                                <div class="flex justify-center flex-wrap gap-1.5">
                                                    <template v-if="row.status == TurnStatus.SUCCESS">
                                                        <template
                                                            v-if="
                                                                row.response?.Result?.MeetingAssistance
                                                                    ?.MeetingAssistance?.Keywords?.length
                                                            ">
                                                            <span
                                                                v-for="(
                                                                    item, index
                                                                ) in row.response.Result.MeetingAssistance.MeetingAssistance.Keywords.slice(
                                                                    0,
                                                                    3
                                                                )"
                                                                :key="index"
                                                                class="px-2 py-0.5 rounded-md bg-gray-50 border border-br-extra-light text-[11px] font-medium text-tx-regular">
                                                                {{ item }}
                                                            </span>
                                                        </template>
                                                        <span v-else class="text-xs text-tx-placeholder">内容为空</span>
                                                    </template>

                                                    <div
                                                        v-else-if="
                                                            row.status == TurnStatus.ING ||
                                                            row.status == TurnStatus.WAITING
                                                        "
                                                        class="flex items-center gap-1.5 text-primary">
                                                        <div class="w-1 h-1 rounded-full bg-primary animate-ping"></div>
                                                        <span class="text-xs font-black uppercase tracking-wider"
                                                            >Processing...</span
                                                        >
                                                    </div>
                                                    <div
                                                        v-else-if="row.status == TurnStatus.ERROR"
                                                        class="flex items-center gap-1.5 text-red-500">
                                                        <Icon name="el-icon-Warning" :size="12" color="#EF4444" />
                                                        <span class="text-xs font-black">转写异常</span>
                                                    </div>
                                                </div>
                                            </template>
                                        </ElTableColumn>

                                        <ElTableColumn label="视频时长" width="100">
                                            <template #default="{ row }">
                                                <div class="text-xs font-mono font-medium text-tx-secondary">
                                                    {{
                                                        getDuration(
                                                            row.response?.Result?.Transcription?.Transcription
                                                                ?.AudioInfo?.Duration
                                                        ) || "--:--"
                                                    }}
                                                </div>
                                            </template>
                                        </ElTableColumn>

                                        <ElTableColumn label="记录时间" width="140">
                                            <template #default="{ row }">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-medium text-tx-regular">{{
                                                        dayjs(row.create_time).format("MM月DD日")
                                                    }}</span>
                                                    <span class="text-[10px] text-tx-placeholder">{{
                                                        dayjs(row.create_time).format("HH:mm")
                                                    }}</span>
                                                </div>
                                            </template>
                                        </ElTableColumn>

                                        <ElTableColumn label="管理" width="80" align="right">
                                            <template #default="{ row }">
                                                <div class="flex justify-end">
                                                    <ElPopover
                                                        :show-arrow="false"
                                                        placement="bottom-end"
                                                        popper-class="!rounded-[16px] !border-[#F1F5F9] !p-1.5 !shadow-light">
                                                        <template #reference>
                                                            <div
                                                                class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-[#F1F5F9] cursor-pointer transition-all">
                                                                <Icon name="el-icon-MoreFilled" color="#94A3B8" />
                                                            </div>
                                                        </template>

                                                        <div class="flex flex-col p-1 gap-1">
                                                            <div
                                                                v-if="row.status == TurnStatus.ERROR"
                                                                class="table-action-item"
                                                                @click="handleAgain(row)">
                                                                <Icon name="el-icon-Refresh" />
                                                                <span>重试任务</span>
                                                            </div>
                                                            <div
                                                                class="h-[1px] bg-[#F1F5F9] my-1"
                                                                v-if="row.status == TurnStatus.ERROR"></div>
                                                            <div
                                                                class="table-action-item !text-red-500 hover:!bg-red-50"
                                                                @click="handleDelete(row.id)">
                                                                <Icon name="el-icon-Delete" :size="14" />
                                                                <span>永久删除</span>
                                                            </div>
                                                        </div>
                                                    </ElPopover>
                                                </div>
                                            </template>
                                        </ElTableColumn>
                                    </ElTable>
                                </div>
                            </template>

                            <div
                                v-else
                                class="py-20 flex flex-col items-center bg-gray-50/50 rounded-2xl border-2 border-dashed border-br">
                                <ElEmpty description="还没有任何转写记录" />
                            </div>
                        </template>

                        <div class="mt-[100px] flex flex-col items-center" v-else>
                            <div class="ai-loader-ring"></div>
                            <div class="text-sm font-medium text-tx-placeholder mt-6 tracking-widest">
                                正在调取云端记录...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ElScrollbar>
        <div class="sticky -bottom-4 left-0 w-full flex justify-center items-center gap-1 my-4 z-[999]">
            <Icon name="el-icon-InfoFilled" color="#B3B3B3"></Icon>
            <span class="text-[#B3B3B3] text-xs">免责声明：内容由AI大模型生成，请仔细甄别。</span>
        </div>
        <AddAudio v-if="showAdd" ref="addAudioRef" @close="showAdd = false" @success="resetLoop()" />
        <KnbBind v-if="showKnbBind" ref="knbBindRef" @close="showKnbBind = false" />
    </div>
</template>

<script setup lang="ts">
import { dayjs } from "element-plus";
import { TurnStatus } from "../../_enums";
import useHandleApi from "../../_hooks/useHandleApi";
import AddAudio from "../../_components/add-audio.vue";
import KnbBind from "@/components/knb-bind/index.vue";

const emit = defineEmits(["openRealTime"]);

const router = useRouter();

const mainCardLists = [
    {
        title: "开启实时语音",
        desc1: "实时语音转文字",
        desc2: "同步口译，智能总结要点",
        class: "meeting-card",
    },
    {
        title: "上传音视频文件",
        desc1: "即刻转写文字",
        desc2: "智能区分发言人，掌握内容",
        class: "audio-card",
    },
    {
        title: "视频链接转写",
        desc1: "输入视频链接",
        desc2: "无需下载，智能提炼要点",
        class: "free-card",
    },
];

const addAudioRef = shallowRef<InstanceType<typeof AddAudio>>();
const showAdd = ref(false);

const handleMainCard = async (key: string) => {
    if (key === "audio-card") {
        showAdd.value = true;
        await nextTick();
        addAudioRef.value?.open();
    } else if (key === "meeting-card") {
        router.push("/app/internal/_meeting_minutes/realtime");
    } else {
        feedback.msgWarning("暂未开放");
    }
};

const { pager, getLists, handleAgain, handleDelete, handleItem, handleTrain, formatName, getDuration } = useHandleApi({
    onSuccess: (type: string) => {
        loopLists();
    },
});

const isLoop = ref(false);
const loopTimer = ref<NodeJS.Timeout>();
const loopLists = async () => {
    await getLists();
    // 检测列表中是否有未完成的会议,
    const unFinishLists = pager.lists.filter(
        (item: any) => item.status == TurnStatus.ING || item.status == TurnStatus.WAITING
    );
    if (unFinishLists.length > 0) {
        isLoop.value = true;
        loopTimer.value = setTimeout(() => {
            loopLists();
        }, 2000);
    } else {
        isLoop.value = false;
        clearTimeout(loopTimer.value);
    }
};

const resetLoop = () => {
    clearTimeout(loopTimer.value);
    loopLists();
};

const showKnbBind = ref(false);
const knbBindRef = shallowRef<InstanceType<typeof KnbBind>>();
const onTrain = (item: any) => {
    handleTrain(item, async (result: any) => {
        showKnbBind.value = true;
        await nextTick();
        knbBindRef.value?.open();
        knbBindRef.value?.setFormData(result);
    });
};

onMounted(async () => {
    loopLists();
});

onUnmounted(() => {
    isLoop.value = false;
    clearTimeout(loopTimer.value);
});
</script>

<style scoped lang="scss">
.meeting-minutes-page {
    @apply w-full h-full relative pb-[40px];

    &::after {
        content: "";
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
        background: url("../../_assets/images/home_bg.png");
        background-size: 100% auto;
        background-repeat: no-repeat;
        z-index: -1;
    }
    .meeting-card,
    .free-card,
    .audio-card {
        width: calc(100% / 3 - 10px);
        background-position: 0 0;
        background-size: 100% 100%;
        @apply relative z-10 cursor-pointer bg-no-repeat transition-all duration-200;
        &::after {
            content: "";
            @apply w-full h-0 block pb-[95%] transition-all duration-200;
        }

        .icon,
        .active-icon {
            background-position: 0 0;
            transition: 0.1s ease-in-out 0.1s;
            @apply w-[112px] h-[126px] absolute -top-[48px] left-[25px] bg-no-repeat bg-cover;
        }
        .active-icon {
            opacity: 0;
            transform: scale(0.8);
        }
        .container {
            @apply absolute top-0 left-0 w-full h-full flex flex-col justify-center px-6 text-[#27264d] group-hover:text-white;
        }
        &:hover {
            transform: translateY(-10px);
            &::after {
                @apply pb-[100%];
            }

            .active-icon {
                opacity: 1;
                transition-delay: 0.2s;
                transform: scale(1);
            }
            .icon {
                opacity: 0;
                transform: translateY(-13px);
                transition-delay: 0s;
            }
            .container {
                .title,
                .desc {
                    @apply text-white;
                }
            }
        }
    }
    .meeting-card {
        background-image: url("../../_assets/images/btn_meeting_cloud.png");
        .icon {
            background-image: url("../../_assets/images/hysq.svg");
        }
        .active-icon {
            background-image: url("../../_assets/images/hysq_active.svg");
        }
        &:hover {
            background-image: url("../../_assets/images/btn_meeting_active_cloud.png");
        }
    }
    .audio-card {
        background-image: url("../../_assets/images/btn_audio.png");
        .icon {
            background-image: url("../../_assets/images/wkbb.svg");
        }
        .active-icon {
            background-image: url("../../_assets/images/wkbb_active.svg");
        }
        &:hover {
            background-image: url("../../_assets/images/btn_audio_active.png");
        }
    }
    .free-card {
        background-image: url("../../_assets/images/btn_free_lab.png");
        .icon {
            background-image: url("../../_assets/images/kbk.svg");
        }
        .active-icon {
            background-image: url("../../_assets/images/kbk_active.svg");
        }
        &:hover {
            background-image: url("../../_assets/images/btn_free_lab_active.png");
        }
    }
}

:deep(.el-table) {
    thead th.el-table__cell.is-leaf {
        border-top: none;
    }
}

.ai-loader-ring {
    @apply w-12 h-12 border-4 border-gray-100 border-t-primary rounded-full;
    animation: spin 1s cubic-bezier(0.76, 0.35, 0.2, 0.7) infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
