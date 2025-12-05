<template>
    <scroll-view
        scroll-y
        class="h-full"
        id="scrollView"
        ref="scrollbarContentRef"
        :scroll-top="scrollTop"
        @scroll="handleScroll">
        <view class="flex flex-col gap-y-4 px-4" v-if="getContentList.length > 0">
            <view v-for="(item, index) in getContentList" :key="index" class="content-item" ref="contentItemRef">
                <view class="flex items-center gap-2 px-3">
                    <view class="flex items-center gap-x-2">
                        <image :src="avatarList[item.SpeakerId - 1]" class="w-[48rpx] h-[48rpx]"></image>
                        <text class="text-[#7F7F94] text-xs">发言人{{ item.SpeakerId }}</text>
                    </view>
                    <view class="text-[#7F7F94] text-xs">{{ getParagraphBeginTime(item.Words) }}</view>
                </view>
                <view
                    class="rounded-xl bg-white p-4 mt-3"
                    ref="paragraphRef"
                    :style="item.style"
                    @click="handleParagraphClick(item.Words)">
                    <view class="leading-[48rpx]" ref="originalRef">
                        <text v-for="word in item.Words" :class="word.class">{{ word.Text }}</text>
                    </view>
                    <template v-if="detail.translation">
                        <view class="my-3">
                            <u-line />
                        </view>
                        <view class="leading-[48rpx] break-all" ref="translationRef">
                            <text v-for="word in getCurrentTranslationContent(index).Sentences">{{ word.Text }}</text>
                        </view>
                    </template>
                </view>
            </view>
        </view>
        <view v-else class="mt-[100rpx]">
            <Empty title="这里空空如也~" />
        </view>
        <view
            class="fixed bottom-4 right-4 p-1 bg-[rgba(44,44,54,.8)] rounded-lg z-[888] leading-[0]"
            v-if="disabledContentScroll && currentParagraphIndex > -1"
            @click.stop="positionContentScroll">
            <image src="@/ai_modules/meeting_minutes/static/icons/crosshair.svg" class="w-[40rpx] h-[40rpx]"></image>
        </view>
    </scroll-view>
</template>

<script setup lang="ts">
import { useAppStore } from "@/stores/app";
import { formatAudioTime, getRect } from "@/utils/util";
import Empty from "../../components/empty/empty.vue";

const props = defineProps<{
    detail: any;
    show: boolean;
}>();

const emit = defineEmits(["update-time"]);

const appStore = useAppStore();
const avatarList = computed(() => {
    return appStore.getMeetingConfig.avatars;
});

const scrollTop = ref(0);
const handleScroll = (e: any) => {
    // scrollTop.value = e.detail.scrollTop;
    if (isProgrammaticScroll.value) return;
    disabledContentScroll.value = true;
};

const getContentList = computed(() => {
    const { response } = props.detail;
    const contentList = response?.Result?.Transcription?.Transcription?.Paragraphs;
    if (contentList && contentList.length > 0) {
        return contentList;
    }
    return [];
});

// 获取翻译的内容
const getCurrentTranslationContent = (index: number) => {
    const { response } = props.detail;
    const translationContent = response?.Result?.Translation?.Translation?.Paragraphs;
    if (translationContent && translationContent.length > 0) {
        return translationContent[index];
    }
    return [];
};

const contentItemRef = ref<HTMLElement[]>([]);
const paragraphRef = ref<HTMLElement[]>([]);
const contentItemRect = ref<any>([]);
const scrollbarContentRef = ref<any>(null);
const currentParagraphIndex = ref(-1);
const disabledContentScroll = ref(false);
const isProgrammaticScroll = ref(false);

// 获取段落开始时间
const getParagraphBeginTime = (words: any) => {
    if (words && words.length == 0) return "00:00";
    const beginTime = words[0].Start;
    return formatAudioTime(beginTime / 1000);
};

// 点击当前段落更新音频播放时间
const handleParagraphClick = (words: any) => {
    const beginTime = words[0].Start;
    emit("update-time", beginTime / 1000);
};

const { proxy }: any = getCurrentInstance();
const getContentItemRect = async () => {
    await nextTick();
    const result: any = (await getRect(".content-item", true, proxy)) || [];
    contentItemRect.value = result;
};

const contentScroll = async (currentTime: number = 0) => {
    const contentList = getContentList.value;
    //@ts-ignore
    currentTime = parseInt(currentTime);
    currentParagraphIndex.value = contentList.findIndex(
        (item: any) => currentTime >= item.Words[0].Start && currentTime <= item.Words[item.Words.length - 1].End
    );
    getContentList.value.forEach((item: any) => {
        item.style = "";
        item.Words.forEach((word: any) => {
            word.class = "";
        });
    });
    if (currentParagraphIndex.value !== -1) {
        const currentItem = getContentList.value[currentParagraphIndex.value];
        currentItem.style = {
            backgroundColor: "#0065FB",
            color: "#ffffff99",
        };
        currentItem.Words.forEach((word: any, wordIndex: number) => {
            if (currentTime >= word.Start && currentTime <= currentItem.Words[currentItem.Words.length - 1].End) {
                word.class = "!text-white";
            } else {
                word.class = "";
            }
        });
        if (contentItemRect.value && contentItemRect.value.length > 0 && !disabledContentScroll.value) {
            setScrollTop();
        }
    }
};

const positionContentScroll = () => {
    scrollTop.value = 0;
    // 这里是为了延迟设置scrollTop，避免在滚动的时候，scrollTop没有发生改变，导致scroll-view没有滚动
    setTimeout(() => {
        setScrollTop();
    }, 30);
    setTimeout(() => {
        disabledContentScroll.value = false;
    }, 100);
};

const setScrollTop = () => {
    isProgrammaticScroll.value = true;
    const { top, height } = contentItemRect.value[currentParagraphIndex.value];
    scrollTop.value = top - 240;
    setTimeout(() => {
        isProgrammaticScroll.value = false;
    }, 100);
};

watch(
    () => props.show,
    (value) => {
        if (value) {
            getContentItemRect();
        }
    }
);

defineExpose({
    contentScroll,
});
</script>

<style scoped></style>
