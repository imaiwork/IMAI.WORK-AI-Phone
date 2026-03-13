<template>
    <view class="h-screen flex flex-col">
        <u-navbar
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }"
            :custom-back="back">
        </u-navbar>
        <view class="px-4 font-medium text-[#000000]/50">
            <template v-if="taskType == TaskTypeEnum.KEYWORD">
                识别关键词<text class="text-primary">{{ dataTotal }}</text
                >个
            </template>
            <template v-else-if="taskType == TaskTypeEnum.CLUE">
                获取客资<text class="text-primary">{{ dataTotal }}</text
                >条
            </template>
            <template v-else-if="taskType == TaskTypeEnum.ADD_WECHAT">
                加好友<text class="text-primary">{{ dataTotal }}</text
                >人
            </template>
            <template v-else>
                触达<text class="text-primary">{{ dataTotal }}</text
                >人
            </template>
        </view>
        <view class="px-4" v-if="taskType == TaskTypeEnum.CLUE">
            <u-tabs
                :list="tabList"
                :current="currentTab"
                bg-color="transparent"
                font-size="26rpx"
                @change="handleTabChange"></u-tabs>
        </view>
        <view class="grow min-h-0 mt-4 pb-5">
            <z-paging
                ref="pagingRef"
                v-model="dataList"
                :fixed="false"
                :loading-more-enabled="taskType == TaskTypeEnum.CLUE"
                :safe-area-inset-bottom="true"
                @query="queryList">
                <view class="px-4">
                    <template v-if="taskType == TaskTypeEnum.CIRCLE">
                        <view class="flex flex-col gap-y-[24rpx]">
                            <view v-for="item in dataList" :key="item.id">
                                <circle-card :item="item" />
                            </view>
                        </view>
                    </template>
                    <template v-if="taskType == TaskTypeEnum.CLOSURE">
                        <view class="flex flex-col gap-y-[24rpx]">
                            <view v-for="(item, index) in dataList" :key="index">
                                <closure-card :item="item" />
                            </view>
                        </view>
                    </template>
                    <template v-if="taskType == TaskTypeEnum.CLUE">
                        <view class="flex flex-col gap-y-[24rpx]">
                            <view v-for="item in dataList" :key="item.id">
                                <clue-card :item="item" />
                            </view>
                        </view>
                    </template>
                    <template v-if="taskType == TaskTypeEnum.ADD_WECHAT">
                        <view class="flex flex-col gap-y-[24rpx]">
                            <view v-for="item in dataList" :key="item.id">
                                <wechat-card :item="item" />
                            </view>
                        </view>
                    </template>
                    <template v-if="taskType == TaskTypeEnum.KEYWORD">
                        <view class="flex flex-col gap-y-[24rpx]">
                            <view v-for="item in dataList" :key="item.id">
                                <keyword-card :item="item" @view-detail="handleViewDetail(item)" />
                            </view>
                        </view>
                    </template>
                </view>
                <template #empty>
                    <empty />
                </template>
            </z-paging>
        </view>
    </view>
</template>

<script setup lang="ts">
import { getTaskKeywordList, getTaskClosureList, getTaskCircleLikeList, getTaskAddWechatList } from "@/api/device";
import { getTaskClue } from "@/api/sph";
import CircleCard from "./components/circle-card.vue";
import ClosureCard from "./components/closure-card.vue";
import ClueCard from "./components/clue-card.vue";
import KeywordCard from "./components/keyword-card.vue";
import WechatCard from "./components/wechat-card.vue";

enum TaskTypeEnum {
    CIRCLE = "circle",
    COMMENT = "comment",
    CLOSURE = "closure",
    CLUE = "clue",
    ADD_WECHAT = "add_wechat",
    KEYWORD = "keyword",
    PRIVATE_MESSAGE = "private_message",
}

const pagingRef = ref<any>(null);

const taskId = ref("");
const taskSubId = ref("");
const taskType = ref<TaskTypeEnum>(TaskTypeEnum.KEYWORD);

const tabList = ref([
    {
        name: "全部",
        value: null,
    },
    {
        name: "有效线索",
        value: 1,
    },
    {
        name: "内含有效线索",
        value: 3,
    },
    {
        name: "无效线索",
        value: 2,
    },
]);
const currentTab = ref(0);

const dataList = ref<any[]>([]);
const dataTotal = ref(0);

const clueKeyword = ref("");
const dataInfo = ref<any>({});
const back = () => {
    if (clueKeyword.value) {
        clueKeyword.value = "";
        taskType.value = TaskTypeEnum.KEYWORD;
        currentTab.value = 0;
        pagingRef.value.reload();
        return;
    }
    uni.navigateBack();
};

const handleViewDetail = (item: any) => {
    taskType.value = TaskTypeEnum.CLUE;
    clueKeyword.value = item.exec_keyword;
    pagingRef.value.reload();
};

const handleTabChange = (index: number) => {
    currentTab.value = index;
    pagingRef.value.reload();
};

const queryList = async (page_no: number, page_size: number) => {
    try {
        switch (taskType.value) {
            case TaskTypeEnum.KEYWORD:
                var { all_number_of_recognitions, keyword_list } = await getTaskKeywordList({
                    id: taskId.value,
                    page_no,
                    page_size,
                });
                dataTotal.value = all_number_of_recognitions;
                dataList.value = keyword_list;
                pagingRef.value.complete(keyword_list);
                return;
            case TaskTypeEnum.CLUE:
                var { lists: clue_list, count: clues_number } = await getTaskClue({
                    task_id: taskSubId.value,
                    page_no,
                    page_size,
                    status: tabList.value[currentTab.value].value || "",
                    exec_keyword: clueKeyword.value,
                });
                if (currentTab.value == 0) {
                    dataTotal.value = clues_number;
                }
                pagingRef.value.complete(clue_list);

                return;

            case TaskTypeEnum.CLOSURE:
                var {
                    list: comment_list,
                    setting_info,
                    touch_number,
                    task_info,
                } = await getTaskClosureList({
                    id: taskId.value,
                    page_no,
                    page_size,
                });
                dataTotal.value = touch_number;
                pagingRef.value.complete(
                    comment_list.map((item: any) => ({
                        ...item,
                        ...setting_info,
                        marker_method: setting_info?.marker_method.map((item: any) => parseInt(item)),
                    }))
                );
                return;
            case TaskTypeEnum.CIRCLE:
                var { list: circle_like_list, touch_number } = await getTaskCircleLikeList({
                    id: taskId.value,
                    page_no,
                    page_size,
                    type: dataInfo.value.type,
                });
                dataTotal.value = touch_number;
                pagingRef.value.complete(circle_like_list);
                return;
            case TaskTypeEnum.ADD_WECHAT:
                var { list: add_wechat_list, add_wechat_number } = await getTaskAddWechatList({
                    id: taskId.value,
                    page_no,
                    page_size,
                    type: dataInfo.value.type,
                });
                dataTotal.value = add_wechat_number;
                pagingRef.value.complete(add_wechat_list);
            default:
                break;
        }
    } catch (error) {
        pagingRef.value.complete([]);
    }
};

onLoad((options: any) => {
    taskId.value = options.task_id;
    taskType.value = options.type;
    taskSubId.value = options.sub_id;
    if (options.data_info) {
        dataInfo.value = JSON.parse(options.data_info);
    }
});
</script>

<style scoped></style>
