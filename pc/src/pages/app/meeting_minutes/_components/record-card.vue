<template>
    <div
        class="bg-white rounded-2xl h-auto cursor-pointer border border-[#e8eaf2] group hover:border-primary hover:shadow-light"
        @click="handleItem(item)">
        <div class="flex flex-col items-center">
            <div class="rounded-2xl border-[4px] border-white w-full h-auto">
                <div class="content-box">
                    <div v-if="item.status == TurnStatus.SUCCESS" class="absolute inset-0">
                        <template v-if="getResult">
                            <div class="success-box">
                                <img
                                    src="../_assets/images/tps.png"
                                    class="absolute top-[20px] left-[24px] w-[40px] h-[27px]" />
                            </div>
                            <div class="absolute top-0 left-0 pt-[16px] px-[24px] pb-[14px] z-2 w-full h-full">
                                <div
                                    class="text-[#585a73] text-xs indent-8 h-[60px] my-[10px] leading-[20px] w-full line-clamp-3">
                                    {{ getResult }}
                                </div>
                                <div
                                    class="h-[16px] absolute bottom-[13px] left-[24px] overflow-hidden mt-1"
                                    style="width: calc(100% - 48px)">
                                    <img src="../_assets/images/audio_spectrum.png" class="h-[16px] max-w-none" />
                                </div>
                            </div>
                            <div
                                class="absolute rounded bg-[rgba(0,0,0,0.27)] right-[20px] bottom-[12px] flex items-center justify-center h-[20px] py-[2px] px-1">
                                <span class="text-xs text-white">
                                    {{ getDuration }}
                                </span>
                            </div>
                        </template>
                        <div v-else class="success-empty-box">
                            <Icon name="local-icon-audio_mic" :size="56"></Icon>
                        </div>
                    </div>
                    <div
                        class="absolute top-0 left-0 w-full h-full flex items-center justify-center"
                        v-else-if="item.status == TurnStatus.ERROR">
                        <img src="../_assets/images/error.png" class="w-[68px] h-[68px]" />
                    </div>
                    <div
                        class="error-box a"
                        v-else-if="item.status == TurnStatus.ING || item.status == TurnStatus.WAITING">
                        <img src="../_assets/images/audio_loading.gif" class="w-[68px] h-[68px]" />
                    </div>
                </div>
                <div class="w-full h-[76px] py-3 px-6">
                    <div class="text-ellipsis whitespace-nowrap overflow-hidden font-semibold">
                        {{ formatName(item.name) }}
                    </div>
                    <div class="mt-2">
                        <template v-if="item.status == TurnStatus.SUCCESS">
                            <div
                                class="flex flex-wrap gap-2 overflow-hidden max-h-[20px]"
                                v-if="getTags && getTags.length">
                                <div
                                    v-for="(tag, index) in getTags"
                                    :key="index"
                                    class="text-xs text-[#8f91a8] px-2 flex justify-center items-center bg-[#f7f8fc] h-[20px] rounded">
                                    {{ tag }}
                                </div>
                            </div>
                            <div class="text-xs text-[#8f91a8]" v-else>内容为空</div>
                        </template>
                        <div
                            class="text-xs text-[#8f91a8]"
                            v-else-if="item.status == TurnStatus.ING || item.status == TurnStatus.WAITING">
                            转写中
                        </div>
                    </div>
                </div>
                <div class="px-[24px] pb-[20px] flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2 justify-end grow">
                        <div class="text-[#8f91a8] text-xs">
                            {{ dayjs(item.create_time).format("MM/DD HH:mm") }}
                        </div>
                        <div
                            class="group-hover:inline-block hidden"
                            :class="[active == item.id ? '!inline-block' : '']">
                            <ElPopover
                                placement="bottom"
                                :show-arrow="false"
                                popper-class="!w-[120px] !min-w-[120px] !p-[6px] !rounded-xl "
                                @show="visibleChange(true, item.id)"
                                @hide="visibleChange(false, item.id)">
                                <template #reference>
                                    <div class="leading-[0]">
                                        <Icon name="el-icon-MoreFilled" color="#8f91a8" :size="12"></Icon>
                                    </div>
                                </template>
                                <div class="flex flex-col gap-2">
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg"
                                        v-if="[TurnStatus.ERROR].includes(item.status)">
                                        <ElButton link :icon="Refresh" @click="emit('again', item.id)"> 重试 </ElButton>
                                    </div>
                                    <div class="px-2 py-1 hover:bg-primary-light-9 rounded-lg">
                                        <ElButton link :icon="Delete" @click="emit('delete', item.id)"> 删除 </ElButton>
                                    </div>
                                    <div
                                        class="px-2 py-1 hover:bg-primary-light-9 rounded-lg"
                                        v-if="item.status == TurnStatus.SUCCESS">
                                        <ElButton link :icon="DocumentAdd" @click="onTrain(item)">
                                            训练知识库
                                        </ElButton>
                                    </div>
                                </div>
                            </ElPopover>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { formatAudioTime } from "@/utils/util";
import { Delete, Refresh, DocumentAdd } from "@element-plus/icons-vue";
import { dayjs } from "element-plus";
import { TurnStatus } from "../_enums";
import useHandleApi from "../_hooks/useHandleApi";

const props = defineProps<{
    item: any;
}>();

const emit = defineEmits(["delete", "again", "train"]);

const router = useRouter();

const { formatName, handleItem, handleTrain } = useHandleApi();

const active = ref<number | undefined>();

const visibleChange = (flag: boolean, id: number) => {
    if (!flag) {
        active.value = undefined;
    } else {
        active.value = id;
    }
};

const getTags = computed(() => {
    const { response } = props.item;
    return response.Result?.MeetingAssistance?.MeetingAssistance?.Keywords;
});

const getResult = computed(() => {
    const { response } = props.item;
    return response.Result?.Summarization?.Summarization?.ParagraphSummary;
});

const getDuration = computed(() => {
    const { response } = props.item;
    const { Duration } = response.Result?.Transcription?.Transcription?.AudioInfo;
    if (Duration) {
        return formatAudioTime(Duration / 1000);
    }
    return 0;
});

const onTrain = (item: any) => {
    handleTrain(item, async (result: any) => {
        emit("train", result);
    });
};
</script>

<style scoped lang="scss">
.content-box {
    @apply w-full h-full pt-[50%] relative;
}
.success-box {
    @apply w-full h-full relative bg-cover bg-no-repeat rounded-xl;
    background-image: url("../_assets/images/tps_bg.jpg");
    background-position: left top;
    background-size: cover;
}

.success-empty-box {
    background: radial-gradient(50% 50% at 50% 50%, rgb(243, 242, 255) 0%, rgb(247, 246, 252) 98%);
    @apply w-full h-full flex items-center justify-center rounded-xl;
}

.error-box {
    background: radial-gradient(50% 50% at 50% 50%, rgb(243, 242, 255) 0%, rgb(247, 246, 252) 98%);
    @apply absolute w-full h-full top-0 left-0 flex items-center justify-center rounded-xl;
}
</style>
