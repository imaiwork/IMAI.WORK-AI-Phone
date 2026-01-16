<template>
    <div class="h-full flex flex-col gap-4 pt-4">
        <div class="">
            <div class="font-bold">关键词</div>
            <div class="mt-3">
                <div class="flex flex-wrap gap-3" v-if="getTags">
                    <div v-for="tag in getTags" class="px-3 py-1 bg-[#E8F0FF] text-primary rounded-md">
                        {{ tag }}
                    </div>
                </div>
                <div class="text-[#c8cad9]" v-else>暂无关键词</div>
            </div>
        </div>
        <div class="">
            <div class="font-bold">全文概要</div>
            <div class="mt-3">
                <div class="text-[#585A73] leading-6" v-if="getParameters">
                    {{ getParameters }}
                </div>
                <div class="text-[#c8cad9]" v-else>暂无全文概要</div>
            </div>
        </div>
        <div class="grow min-h-0 flex flex-col">
            <div>
                <ElTabs v-model="activeTab">
                    <ElTabPane label="章节速览" name="1"></ElTabPane>
                    <ElTabPane label="发言总结" name="2"></ElTabPane>
                    <ElTabPane label="问答回顾" name="3"></ElTabPane>
                </ElTabs>
            </div>
            <div class="grow min-h-0 -mx-4 pb-6">
                <ElScrollbar>
                    <div class="px-4">
                        <template v-if="activeTab == '1'">
                            <div class="flex flex-col gap-4" v-if="getSections">
                                <div class="flex gap-4" v-for="(item, index) in getSections" :key="index">
                                    <div class="relative">
                                        <div class="flex items-center gap-2 mt-2">
                                            <div class="w-[40px] text-xs">
                                                {{ formatAudioTime(item.Start / 1000) }}
                                            </div>
                                            <div class="w-2 h-2 rounded-full bg-black"></div>
                                        </div>
                                        <div
                                            class="h-full border-r border-dashed border-primary absolute right-1"
                                            v-if="index < getSections.length - 1"></div>
                                    </div>
                                    <div class="p-6 bg-primary-light-9 rounded-md">
                                        <div>
                                            {{ item.Headline }}
                                        </div>
                                        <div class="mt-2 text-xs text-[#63647C]">
                                            {{ item.Summary }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-[#c8cad9]" v-else>暂无章节速览</div>
                        </template>
                        <template v-if="activeTab == '2'">
                            <div class="flex flex-col gap-3" v-if="getConversational">
                                <div
                                    class="flex gap-3 bg-[#E8F0FF] p-3 rounded-lg"
                                    v-for="item in getConversational"
                                    :key="item">
                                    <div class="flex-shrink-0 w-[65px] min-w-[65px] flex flex-col gap-2 items-center">
                                        <img class="w-[28px] h-[28px]" :src="avatarList[item.SpeakerId - 1]" />
                                        <span class="text-[#585A73] text-xs">{{ item.SpeakerName }}</span>
                                    </div>
                                    <div class="text-[#585A73] text-xs leading-[20px]">
                                        {{ item.Summary }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-[#c8cad9]" v-else>暂无发言总结</div>
                        </template>
                        <template v-if="activeTab == '3'">
                            <div class="flex flex-col gap-3" v-if="getQa">
                                <div v-for="(item, index) in getQa" class="rounded flex overflow-hidden">
                                    <div class="bg-[#DEE5FA] w-[48px] flex-shrink-0 flex items-center justify-center">
                                        <span class="text-xl font-bold text-primary">Q</span>
                                        <span class="text-[10px] font-bold text-[#FF8D1A]">A</span>
                                    </div>
                                    <div class="bg-[#E8F0FF] grow p-3">
                                        <div class="flex gap-2">
                                            <div class="text-primary font-bold">问:</div>
                                            <div class="font-bold">
                                                {{ item.Question }}
                                            </div>
                                        </div>
                                        <div class="flex gap-2 mt-2">
                                            <div class="text-[#FF8D1A] font-bold">答:</div>
                                            <div class="text-xs text-[#585A73] leading-[20px]">
                                                {{ item.Answer }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-[#c8cad9]" v-else>暂无问答回顾</div>
                        </template>
                    </div>
                </ElScrollbar>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { formatAudioTime } from "@/utils/util";
import { useAppStore } from "@/stores/app";

const appStore = useAppStore();

const props = defineProps<{
    detail: any;
}>();

const activeTab = ref("1");

const avatarList = computed(() => {
    return appStore.config.meeting_config.avatars;
});

const getTags = computed(() => {
    const { response } = props.detail;
    if (response) {
        return response.Result?.MeetingAssistance?.MeetingAssistance?.Keywords;
    }
});

// 获取全文摘要
const getParameters = computed(() => {
    const { response } = props.detail;
    if (response) {
        return response.Result?.Summarization?.Summarization?.ParagraphSummary;
    }
});

// 获取章节速览
const getSections = computed(() => {
    const { response } = props.detail;
    if (response) {
        return response.Result?.AutoChapters?.AutoChapters;
    }
});

// 获取发言总结
const getConversational = computed(() => {
    const { response } = props.detail;
    if (response) {
        return response.Result?.Summarization?.Summarization?.ConversationalSummary;
    }
});

// 获取问答回顾
const getQa = computed(() => {
    const { response } = props.detail;
    if (response) {
        return response.Result?.Summarization?.Summarization?.QuestionsAnsweringSummary;
    }
});
</script>

<style scoped lang="scss">
:deep(.el-tabs__nav-wrap) {
    &::after {
        display: none;
    }
    .el-tabs__nav {
        &::after {
            background-color: var(--el-border-color-light);
            bottom: 0;
            content: "";
            height: 1px;
            left: 0;
            width: 100%;
            position: absolute;
        }
        .el-tabs__item {
            color: #89899c;
            &.is-active {
                color: #000000;
                font-weight: bold;
            }
        }
    }
}

$lite: #77778f;
.loading-box {
    @apply h-[69px] w-full relative flex justify-center items-center;
    background: url("../../_assets/images/loading_bg.png") no-repeat center center;
    background-size: 100% 100%;
    .txt {
        color: $lite;
        position: relative;
        font-family: Arial, Helvetica, sans-serif;
        &::after {
            content: "";
            width: 2px;
            height: 2px;
            background: currentColor;
            position: absolute;
            bottom: 4px;
            right: -8px;
            animation: loading 1s linear infinite;
        }
    }
}

@keyframes loading {
    0% {
        box-shadow: 10px 0 rgba($lite, 0), 20px 0 rgba($lite, 0);
    }
    50% {
        box-shadow: 10px 0 rgba($lite, 1), 20px 0 rgba($lite, 0);
    }
    100% {
        box-shadow: 10px 0 rgba($lite, 1), 20px 0 rgba($lite, 1);
    }
}
</style>
