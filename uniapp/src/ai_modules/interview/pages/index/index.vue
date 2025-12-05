<template>
    <view class="h-screen flex flex-col relative" v-if="!loading">
        <view class="bg z-10"></view>
        <view class="relative z-30">
            <u-navbar
                :background="{
                    background: 'transparent',
                }"
                :border-bottom="false"
                title="面试"
                title-bold>
            </u-navbar>
            <view class="">
                <view class="flex justify-between overflow-hidden">
                    <view class="flex flex-col gap-2 mt-10 mx-4">
                        <view class="text-2xl font-bold">HI，{{ state.user_id ? "应聘者" : "今天怎么样？" }}</view>
                        <view class="text-[#707070]">{{
                            state.user_id
                                ? `我是${detail.company || ""}企业的AIHR，请选择您要应聘的岗位！`
                                : "我是您的智能AI面试官，有什么岗位需要帮助您进行招聘？"
                        }}</view>
                    </view>
                    <view class="mr-1 mt-4 flex h-full items-end">
                        <image
                            src="@/ai_modules/interview/static/images/common/interview_img.png"
                            class="w-[268rpx] h-[240rpx]"></image>
                    </view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 py-4 px-[32rpx] box overflow-scroll relative z-20">
            <view class="flex">
                <view class="text-sm bg-[#06A359] rounded-[12rpx] text-white px-3 py-2 font-bold">{{
                    state.job_id ? "在招岗位" : "邀约面试"
                }}</view>
            </view>
            <view class="flex flex-col gap-4 mt-4" v-if="lists.length > 0">
                <view
                    v-for="item in lists"
                    :key="item.id"
                    class="bg-white rounded-[40rpx] p-4 shadow-[0rpx_8rpx_124rpx_rgba(153,171,198,0.18)]">
                    <view class="flex items-center gap-2">
                        <image class="w-[80rpx] h-[80rpx] rounded-full" mode="aspectFill" :src="item.avatar"></image>
                        <text class="text-[32rpx] font-bold">{{ item.name }}</text>
                    </view>
                    <view class="text-[#524B6B] text-xs mt-2 leading-6 whitespace-pre-wrap">
                        {{ item.desc }}
                    </view>
                    <view class="flex items-center justify-between mt-2">
                        <view class="text-[#AAA6B9] text-xs"> {{ formatDate(item.create_time) }} 发布 </view>
                        <view
                            class="rounded-lg py-1 w-[158rpx] text-center text-xs bg-[#F4F3F5] text-[#524B6B]"
                            @click="handleInterviewDetail(item)">
                            {{ state.user_id ? "参加面试" : "分享" }}
                        </view>
                    </view>
                </view>
            </view>
            <view class="" v-else>
                <empty />
            </view>
        </view>
    </view>
</template>

<script lang="ts" setup>
import { getInterviewJobList, getInterviewJobDetail, generateJobLink, interviewCheck } from "@/api/interview";
import { useCopy } from "@/hooks/useCopy";

const state = reactive({
    // 是否是面试者
    job_id: "",
    user_id: "",
});

const lists = ref<any[]>([]);
const detail = ref<any>({});

const formatDate = (date: string) => {
    const timestamp = new Date(date);
    return uni.$u.timeFormat(timestamp, "yyyy/mm/dd");
};

const { copy } = useCopy();
const handleInterviewDetail = async (item: any) => {
    if (state.user_id) {
        uni.showLoading({
            title: "验证中...",
        });
        try {
            const {
                type,
                msg,
                id: record_id,
            } = await interviewCheck({
                job_id: item.id,
            });
            if (type == 1) {
                uni.$u.route({
                    url: "/ai_modules/interview/pages/interview_detail/interview_detail",
                    params: {
                        id: item.id,
                    },
                });
            } else if ([2, 3, 4, 5].includes(type)) {
                uni.$u.route({
                    url: "/ai_modules/interview/pages/full_screen/full_screen",
                    params: {
                        id: item.id,
                        type,
                        record_id,
                    },
                });
            } else {
                uni.hideLoading();
                uni.showToast({
                    title: msg,
                    icon: "none",
                    duration: 3000,
                });
            }
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({
                title: error,
                icon: "none",
                duration: 3000,
            });
        }
    } else {
        uni.showLoading({
            title: "生成中...",
        });
        try {
            const { url } = await generateJobLink({
                job_id: item.id,
            });
            uni.hideLoading();
            copy(url);
        } catch (error) {
            uni.hideLoading();
            uni.showToast({
                title: "生成失败",
                icon: "none",
                duration: 2000,
            });
        }
    }
};
const getLists = async () => {
    const data = await getInterviewJobList({
        user_id: state.user_id,
        job_id: state.job_id,
    });
    lists.value = data;
};

const getJobDetail = async () => {
    if (state.job_id) {
        const data = await getInterviewJobDetail({ id: state.job_id });
        detail.value = data;
    }
};

const loading = ref(false);
const init = async () => {
    loading.value = true;
    try {
        await Promise.allSettled([getLists(), getJobDetail()]);
    } finally {
        loading.value = false;
    }
};

onLoad((options: any) => {
    state.job_id = options.job_id;
    state.user_id = options.user_id;
    init();
});
</script>

<style lang="scss" scoped>
.bg {
    height: 520rpx;
    top: 0;
    z-index: 1;
    left: 0;
    width: 100%;
    position: absolute;
    background: linear-gradient(135deg, rgba(208, 247, 236, 1) 0%, rgba(247, 255, 252, 1) 100%);
}
.box {
    @apply rounded-tl-[40rpx] rounded-tr-[40rpx];
    background: linear-gradient(0, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 1) 100%);

    box-shadow: inset 0rpx 0rpx 4rpx rgba(255, 255, 255, 0.7), inset 0rpx 1rpx 2rpx rgba(255, 255, 255, 0.5),
        inset 0rpx 0rpx 30rpx rgba(255, 255, 255, 0.25), inset 0rpx 30rpx 60rpx rgba(255, 255, 255, 0.2);
}
</style>
