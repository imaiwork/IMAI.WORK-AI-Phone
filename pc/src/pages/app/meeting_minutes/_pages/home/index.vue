<template>
    <div class="meeting-minutes-page dynamic-scroller">
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
                            <div class="font-bold text-[22px] title">
                                {{ item.title }}
                            </div>
                            <div class="mt-4 leading-6 desc">
                                <div>{{ item.desc1 }}</div>
                                <div>{{ item.desc2 }}</div>
                            </div>
                            <div
                                class="mt-4 font-bold text-[#27264D] group-hover:text-white invisible group-hover:visible">
                                <Icon name="el-icon-Right" :size="24"></Icon>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-8 mt-10">
                <div class="text-lg font-bold">最近</div>
                <div class="mt-5">
                    <template v-if="!pager.loading || isLoop">
                        <template v-if="pager.lists.length">
                            <ElCard shadow="never" class="!rounded-xl">
                                <ElTable
                                    :data="pager.lists"
                                    :row-style="{
                                        height: '80px',
                                        cursor: 'pointer',
                                    }"
                                    @row-click="handleItem">
                                    <ElTableColumn prop="name" label="文件" min-width="200" show-overflow-tooltip>
                                        <template #default="{ row }">
                                            <div class="flex items-center gap-4">
                                                <div
                                                    class="bg-[#edecff] rounded flex-shrink-0"
                                                    v-if="
                                                        row.status == TurnStatus.ING || row.status == TurnStatus.WAITING
                                                    ">
                                                    <img
                                                        src="../../_assets/images/audio_transform.gif"
                                                        class="w-9 h-9" />
                                                </div>
                                                <div class="text-base overflow-hidden text-ellipsis whitespace-nowrap">
                                                    {{ formatName(row.name) }}
                                                </div>
                                            </div>
                                        </template>
                                    </ElTableColumn>
                                    <ElTableColumn prop="title" label="详情" min-width="120px">
                                        <template #default="{ row }">
                                            <div
                                                class="overflow-hidden flex flex-wrap gap-2 max-h-[20px] text-xs text-[#8f91a8]">
                                                <template v-if="row.status == TurnStatus.SUCCESS">
                                                    <template
                                                        v-if="
                                                            row.response.Result?.MeetingAssistance?.MeetingAssistance
                                                                ?.Keywords?.length
                                                        ">
                                                        <div
                                                            v-for="item in row.response.Result.MeetingAssistance
                                                                .MeetingAssistance.Keywords"
                                                            class="text-xs text-[#8f91a8] px-2 flex justify-center items-center bg-[#f7f8fc] h-[20px] rounded">
                                                            {{ item }}
                                                        </div>
                                                    </template>
                                                    <template v-else>内容为空</template>
                                                </template>
                                                <template
                                                    v-else-if="
                                                        row.status == TurnStatus.ING || row.status == TurnStatus.WAITING
                                                    ">
                                                    处理中
                                                </template>
                                                <template v-else-if="row.status == TurnStatus.ERROR">
                                                    转写失败
                                                </template>
                                            </div>
                                        </template>
                                    </ElTableColumn>
                                    <ElTableColumn prop="title" label="类型">
                                        <template #default="{ row }">
                                            <div class="text-[#8f91a8] text-xs">
                                                {{ row.task_type == 1 ? "音频速读" : "实时记录" }}
                                            </div>
                                        </template>
                                    </ElTableColumn>
                                    <ElTableColumn prop="created_at" label="时长" width="100px">
                                        <template #default="{ row }">
                                            <div class="text-[#8f91a8] text-xs">
                                                {{
                                                    getDuration(
                                                        row.response?.Result?.Transcription?.Transcription?.AudioInfo
                                                            ?.Duration
                                                    )
                                                }}
                                            </div>
                                        </template>
                                    </ElTableColumn>
                                    <ElTableColumn prop="created_at" label="创建时间" width="140px">
                                        <template #default="{ row }">
                                            <div class="text-[#8f91a8] text-xs">
                                                {{ dayjs(row.create_time).format("MM/DD HH:mm") }}
                                            </div>
                                        </template>
                                    </ElTableColumn>
                                    <ElTableColumn prop="created_at" label="操作" width="60px">
                                        <template #default="{ row }">
                                            <ElPopover
                                                :show-arrow="false"
                                                popper-class="!w-[120px] !min-w-[120px] !p-[6px] !rounded-xl">
                                                <template #reference>
                                                    <ElButton link>
                                                        <Icon
                                                            name="el-icon-MoreFilled"
                                                            color="#8f91a8"
                                                            :size="12"></Icon>
                                                    </ElButton>
                                                </template>
                                                <div class="flex flex-col gap-2">
                                                    <div
                                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                                        v-if="[TurnStatus.ERROR].includes(row.status)">
                                                        <Icon name="el-icon-Refresh"></Icon>
                                                        <span>重试</span>
                                                    </div>
                                                    <div
                                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                                        @click="handleDelete(row.id)">
                                                        <Icon name="el-icon-Delete"></Icon>
                                                        <span>删除</span>
                                                    </div>
                                                    <div
                                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg"
                                                        v-if="row.status == TurnStatus.SUCCESS">
                                                        <ElButton link icon="el-icon-DocumentAdd" @click="onTrain(row)">
                                                            训练知识库
                                                        </ElButton>
                                                    </div>
                                                </div>
                                            </ElPopover>
                                        </template>
                                    </ElTableColumn>
                                </ElTable>
                            </ElCard>
                        </template>
                        <div v-else class="py-4">
                            <ElEmpty description="暂无数据"></ElEmpty>
                        </div>
                    </template>
                    <div class="mt-[100px] flex flex-col items-center" v-else>
                        <Loader />
                        <div class="text-sm text-gray-500 mt-10">加载中...</div>
                    </div>
                </div>
            </div>
            <div class="fixed bottom-0 left-0 w-full flex justify-center items-center gap-1 my-2">
                <Icon name="el-icon-InfoFilled" color="#B3B3B3"></Icon>
                <span class="text-[#B3B3B3] text-xs">免责声明：内容由AI大模型生成，请仔细甄别。</span>
            </div>
        </div>
        <AddAudio v-if="showAdd" ref="addAudioRef" @close="showAdd = false" @success="loopLists" />
        <KnbBind v-if="showKnbBind" ref="knbBindRef" @close="showKnbBind = false" />
    </div>
</template>

<script setup lang="ts">
import { dayjs } from "element-plus";
import { TurnStatus } from "../../_enums";
import AddAudio from "./_components/add-audio.vue";
import useHandleApi from "../../_hooks/useHandleApi";
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
        router.push("/app/meeting_minutes/realtime");
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
    @apply w-full h-full relative overflow-y-auto pb-[40px];

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
::v-deep(.el-table) {
    th.el-table__cell {
        background-color: transparent;
        color: #8f91a8;
    }
    .el-table__cell {
        text-align: left;
    }
}
</style>
