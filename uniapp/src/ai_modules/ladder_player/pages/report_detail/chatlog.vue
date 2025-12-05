<template>
    <view class="pt-4 h-full flex flex-col">
        <view class="grow min-h-0 bg-[#F5F8FF] rounded-[16rpx]">
            <z-paging
                v-model="contentList"
                ref="pagingRef"
                :fixed="false"
                :auto="false"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="p-3">
                    <chatting :content-list="contentList" />
                </view>
                <template #empty>
                    <empty text="暂无对话" />
                </template>
            </z-paging>
        </view>
    </view>
</template>

<script setup lang="ts">
import { lpRecordLists } from "@/api/ladder_player";
import Chatting from "../../components/chatting/chatting.vue";
import { useAppStore } from "@/stores/app";

const props = defineProps<{
    id: any;
    sceneDetail: any;
}>();

const appStore = useAppStore();
const { getLadderConfig } = appStore;

const getCurrVoice = computed(() => {
    const data = appStore.getLadderConfig?.voice || [];
    return data.find((item: any) => item.code == props.sceneDetail?.coach_voice) || {};
});

const contentList = ref<any[]>([]);

const pagingRef = shallowRef();
const queryList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await lpRecordLists({
            page_no,
            page_size,
            analysis_id: props.id,
        });
        const transformedLists = lists
            .map((item: any) => {
                if (item.preliminary_ask) {
                    return {
                        type: 2,
                        reply: item.preliminary_ask,
                        duration: item.preliminary_ask_audio_duration,
                        logo: getCurrVoice.value.logo,
                        link: item.preliminary_ask_audio,
                    };
                } else {
                    return [
                        {
                            type: 1,
                            duration: item.ask_audio_duration,
                            message: item.ask,
                            link: item.ask_audio,
                            tips: {
                                performance: item.performance || "暂无内容",
                                speechcraft: item.speechcraft || "暂无内容",
                            },
                        },
                        {
                            type: 2,
                            reply: item.reply,
                            duration: item.reply_audio_duration,
                            logo: getCurrVoice.value.logo,
                            link: item.reply_audio,
                        },
                    ];
                }
            })
            .flat();

        pagingRef.value?.complete(transformedLists);
    } catch (error) {
        pagingRef.value?.complete([]);
    }
};

const init = async () => {
    await nextTick();
    uni.showLoading({
        title: "加载中",
    });
    try {
        await pagingRef.value?.reload();
    } finally {
        uni.hideLoading();
    }
};

onMounted(() => {
    init();
});
</script>

<style scoped></style>
