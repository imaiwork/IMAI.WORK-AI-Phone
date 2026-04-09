<template>
    <view class="min-h-screen bg-[#F4F6FB] pb-[120rpx]">
        <view class="w-full h-[670rpx] absolute top-0 left-0">
            <image :src="`${config.baseUrl}static/images/mp/index_bg.png`" class="w-full h-full" mode="aspectFill" />
        </view>

        <view class="px-[48rpx] pt-[140rpx] relative z-10">
            <view class="text-[52rpx] font-bold text-white leading-tight">AI 智能数字员工</view>
            <view class="text-[26rpx] text-[#ffffff]/60 mt-[12rpx]">助力企业高效增长，让业务自动运转</view>
            <view class="mt-[32rpx] flex items-center gap-[20rpx]">
                <view class="flex items-center gap-[10rpx] bg-[#ffffff]/15 rounded-[100rpx] px-[28rpx] py-[14rpx]">
                    <view class="w-[12rpx] h-[12rpx] rounded-full bg-[#52FFB0]" />
                    <text class="text-white text-[24rpx] font-medium">1台手机 = 6个员工</text>
                </view>
                <view class="flex items-center gap-[10rpx] bg-[#ffffff]/15 rounded-[100rpx] px-[28rpx] py-[14rpx]">
                    <view class="w-[12rpx] h-[12rpx] rounded-full bg-[#FFD452]" />
                    <text class="text-white text-[24rpx] font-medium">7×24h 自动运行</text>
                </view>
            </view>
        </view>

        <view class="px-[28rpx] mt-[50rpx] relative z-10">
            <view class="grid gap-[16rpx]" style="grid-template-columns: repeat(2, 1fr)">
                <view
                    class="rounded-[28rpx] p-[36rpx] relative overflow-hidden"
                    style="
                        grid-area: 1/1/3/2;
                        background: linear-gradient(160deg, #f0f2fd 0%, #fefefe 100%);
                        box-shadow: 0 8rpx 40rpx rgba(43, 91, 245, 0.1);
                        border: 3rpx solid rgba(255, 255, 255, 0.9);
                    "
                    @click="toPage('/ai_modules/person/pages/index/index')">
                    <view class="text-[36rpx] font-bold text-[#1a1a2e]">人设 IP</view>
                    <view class="text-[#000000]/35 text-[22rpx] mt-[10rpx]">24h 任务内容配置</view>
                    <view
                        class="mt-[28rpx] inline-flex items-center gap-[8rpx] bg-primary rounded-[100rpx] px-[28rpx] py-[14rpx]">
                        <text class="text-white text-[24rpx] font-medium">立即添加</text>
                    </view>
                    <view class="absolute bottom-[10rpx] right-[10rpx]">
                        <image
                            :src="`${config.baseUrl}static/images/mp/blue_notepad_4562.png`"
                            class="w-[130rpx] h-[120rpx]" />
                    </view>
                </view>

                <view
                    class="rounded-[28rpx] p-[32rpx] relative overflow-hidden h-[150rpx]"
                    style="
                        background: linear-gradient(160deg, #f0f2fd 0%, #fefefe 100%);
                        box-shadow: 0 8rpx 40rpx rgba(43, 91, 245, 0.08);
                        border: 3rpx solid rgba(255, 255, 255, 0.9);
                    "
                    @click="toPage('/packages/pages/operate_case/operate_case')">
                    <view class="text-[30rpx] font-bold text-[#1a1a2e]">运营案例</view>
                    <view class="text-[#000000]/35 text-[22rpx] mt-[8rpx]">行业专属模版</view>
                    <view class="absolute bottom-[-28rpx] right-[8rpx]">
                        <image
                            :src="`${config.baseUrl}static/images/mp/aurora_spark_7842.png`"
                            class="w-[110rpx] h-[110rpx]" />
                    </view>
                </view>

                <view
                    class="rounded-[28rpx] p-[32rpx] relative overflow-hidden h-[150rpx]"
                    style="
                        background: linear-gradient(160deg, #f0f2fd 0%, #fefefe 100%);
                        box-shadow: 0 8rpx 40rpx rgba(43, 91, 245, 0.08);
                        border: 3rpx solid rgba(255, 255, 255, 0.9);
                    "
                    @click="toPage('/ai_modules/device/pages/index/index')">
                    <view class="text-[30rpx] font-bold text-[#1a1a2e]">设备列表</view>
                    <view class="mt-[10rpx] flex items-center gap-[10rpx]">
                        <text class="text-[#000000]/35 text-[22rpx]">我的设备</text>
                        <text class="text-primary font-bold text-[26rpx]">{{ deviceCount }}</text>
                    </view>
                    <view class="absolute bottom-[-28rpx] right-[8rpx]">
                        <image
                            :src="`${config.baseUrl}static/images/mp/phantom_tide_1763.png`"
                            class="w-[110rpx] h-[100rpx]" />
                    </view>
                </view>
            </view>

            <view class="mt-[20rpx] bg-white rounded-[28rpx]" style="box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.06)">
                <view class="grid grid-cols-4">
                    <view
                        class="flex flex-col items-center py-[32rpx] gap-[12rpx]"
                        @click="toPage('/ai_modules/device/pages/task_calendar/task_calendar')">
                        <image
                            :src="`${config.baseUrl}static/images/mp/ember_glow_6309.png`"
                            class="w-[80rpx] h-[80rpx]" />
                        <text class="text-[24rpx] font-medium text-[#333]">任务日历</text>
                    </view>
                    <view
                        class="flex flex-col items-center py-[32rpx] gap-[12rpx]"
                        @click="toPage('/ai_modules/device/pages/task_statement/task_statement')">
                        <image
                            :src="`${config.baseUrl}static/images/mp/frost_bloom_2847.png`"
                            class="w-[80rpx] h-[80rpx]" />
                        <text class="text-[24rpx] font-medium text-[#333]">数据报表</text>
                    </view>
                    <view
                        class="flex flex-col items-center py-[32rpx] gap-[12rpx]"
                        @click="toPage('/ai_modules/device/pages/task_client_assets/task_client_assets')">
                        <image
                            :src="`${config.baseUrl}static/images/mp/lunar_echo_5512.png`"
                            class="w-[80rpx] h-[80rpx]" />
                        <text class="text-[24rpx] font-medium text-[#333]">客资线索</text>
                    </view>
                    <view
                        class="flex flex-col items-center py-[32rpx] gap-[12rpx]"
                        @click="toPage('/packages/pages/course/course')">
                        <image
                            :src="`${config.baseUrl}static/images/mp/cobalt_flare_8834.png`"
                            class="w-[80rpx] h-[80rpx]" />
                        <text class="text-[24rpx] font-medium text-[#333]">使用教程</text>
                    </view>
                </view>
            </view>

            <view class="mt-[32rpx]" @click="toPage('/ai_modules/device/pages/task_statement/task_statement')">
                <view class="flex items-center justify-between mb-[16rpx] px-[4rpx]">
                    <view class="flex items-center gap-[16rpx]">
                        <view class="w-[6rpx] h-[32rpx] rounded-full bg-primary" />
                        <text class="font-bold text-[28rpx] text-[#1a1a2e]">今日概览</text>
                        <text class="text-[#000000]/30 text-[22rpx]">{{ getDate }} {{ getWeek }}</text>
                    </view>
                    <view
                        class="text-primary font-medium text-[26rpx] bg-[#EBF1FE] rounded-[100rpx] px-[24rpx] py-[10rpx]"
                        @click="toPage('/ai_modules/device/pages/choose_task_type/choose_task_type')">
                        + 添加任务
                    </view>
                </view>

                <view class="bg-white rounded-[28rpx] p-[36rpx]" style="box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.06)">
                    <view class="flex items-center justify-between">
                        <view class="flex gap-[60rpx]">
                            <view>
                                <view class="text-[22rpx] text-[#000000]/40 font-medium mb-[8rpx]">今日任务</view>
                                <view class="text-[72rpx] font-bold text-primary leading-none">{{
                                    statistics.today_task_count || 0
                                }}</view>
                            </view>
                            <view>
                                <view class="text-[22rpx] text-[#000000]/40 font-medium mb-[8rpx]">已完成</view>
                                <view class="text-[72rpx] font-bold text-[#1a1a2e] leading-none">{{
                                    statistics.today_complete_task_count || 0
                                }}</view>
                            </view>
                        </view>
                        <semi-circle-progress
                            :size="120"
                            :stroke-width="8"
                            :progress="statistics.today_rate || 0"
                            bg-color="#EDF4FB">
                            <view class="text-primary font-bold text-[28rpx]">{{ statistics.today_rate }}%</view>
                        </semi-circle-progress>
                    </view>

                    <view class="my-[28rpx] h-[1rpx] bg-[#F0F0F0]" />

                    <view class="grid grid-cols-3 gap-[16rpx]">
                        <view class="bg-[#F8F9FF] rounded-[20rpx] px-[20rpx] py-[22rpx]">
                            <view class="flex items-center justify-between mb-[10rpx]">
                                <text class="text-[22rpx] text-[#000000]/40">工作设备</text>
                                <image src="/static/images/icons/windows.svg" class="w-[28rpx] h-[28rpx]" />
                            </view>
                            <view class="text-[44rpx] font-bold text-[#1a1a2e]">{{
                                statistics.worker_device_count || 0
                            }}</view>
                        </view>
                        <view class="bg-[#F8F9FF] rounded-[20rpx] px-[20rpx] py-[22rpx]">
                            <view class="flex items-center justify-between mb-[10rpx]">
                                <text class="text-[22rpx] text-[#000000]/40">触达(人)</text>
                                <image src="/static/images/icons/user_star.svg" class="w-[28rpx] h-[28rpx]" />
                            </view>
                            <view class="text-[44rpx] font-bold text-[#1a1a2e]">{{
                                statistics.today_touch_number || 0
                            }}</view>
                        </view>
                        <view class="bg-[#F8F9FF] rounded-[20rpx] px-[20rpx] py-[22rpx]">
                            <view class="flex items-center justify-between mb-[10rpx]">
                                <text class="text-[22rpx] text-[#000000]/40">曝光(人)</text>
                                <image src="/static/images/icons/user_star.svg" class="w-[28rpx] h-[28rpx]" />
                            </view>
                            <view class="text-[44rpx] font-bold text-[#1a1a2e]">{{
                                statistics.today_expose_number || 0
                            }}</view>
                        </view>
                    </view>
                </view>
            </view>

            <view class="mt-[36rpx]" @click="toPage('/ai_modules/device/pages/task_statement/task_statement')">
                <view class="flex items-center gap-[16rpx] mb-[16rpx] px-[4rpx]">
                    <view class="w-[6rpx] h-[32rpx] rounded-full bg-primary" />
                    <text class="font-bold text-[28rpx] text-[#1a1a2e]">实时数据分析</text>
                </view>

                <view class="grid grid-cols-3 gap-[16rpx] mb-[20rpx]">
                    <view
                        class="bg-white rounded-[24rpx] py-[32rpx] flex flex-col items-center"
                        style="box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.05)">
                        <view class="text-[44rpx] font-bold text-primary">{{
                            statistics.seven_expose_number || 0
                        }}</view>
                        <view class="text-[#000000]/40 text-[22rpx] mt-[8rpx]">近7日曝光</view>
                    </view>
                    <view
                        class="bg-white rounded-[24rpx] py-[32rpx] flex flex-col items-center"
                        style="box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.05)">
                        <view class="text-[44rpx] font-bold text-[#F88700]">{{
                            statistics.seven_touch_number || 0
                        }}</view>
                        <view class="text-[#000000]/40 text-[22rpx] mt-[8rpx]">近7日触达</view>
                    </view>
                    <view
                        class="bg-white rounded-[24rpx] py-[32rpx] flex flex-col items-center"
                        style="box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.05)">
                        <view class="text-[44rpx] font-bold text-[#00B478]">{{
                            statistics.seven_intention_number || 0
                        }}</view>
                        <view class="text-[#000000]/40 text-[22rpx] mt-[8rpx]">近7日意向</view>
                    </view>
                </view>

                <view
                    class="bg-white rounded-[28rpx] p-[36rpx] mb-[16rpx]"
                    style="box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.06)">
                    <view class="flex items-center gap-[20rpx]">
                        <image
                            :src="`${config.baseUrl}static/images/mp/solar_drift_4471.png`"
                            class="w-[80rpx] h-[80rpx] flex-shrink-0" />
                        <view class="flex-1">
                            <view class="text-[30rpx] font-bold text-[#1a1a2e]">AI 智能获客</view>
                            <view class="text-[22rpx] text-[#000000]/35 mt-[6rpx]">自动私信 / 评论触达</view>
                        </view>
                        <view
                            class="text-primary text-[22rpx] font-medium bg-[#EBF1FE] px-[20rpx] py-[10rpx] rounded-[100rpx]">
                            转化率 {{ statistics.ai_clues.rate || 0 }}%
                        </view>
                    </view>
                    <view class="mt-[24rpx] flex items-center justify-between text-[24rpx] mb-[14rpx]">
                        <text class="text-[#000000]/40"
                            >触达：<text class="text-[#1a1a2e] font-bold">{{
                                statistics.ai_clues.touch_number || 0
                            }}</text></text
                        >
                        <text class="text-[#000000]/40"
                            >意向：<text class="text-[#1a1a2e] font-bold">{{
                                statistics.ai_clues.expose_number || 0
                            }}</text></text
                        >
                    </view>
                    <u-line-progress :percent="statistics.ai_clues.rate || 0" :show-text="false" />
                </view>

                <view
                    class="bg-white rounded-[28rpx] p-[36rpx] mb-[16rpx]"
                    style="box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.06)">
                    <view class="flex items-center gap-[20rpx]">
                        <image
                            :src="`${config.baseUrl}static/images/mp/neon_pulse_3156.png`"
                            class="w-[80rpx] h-[80rpx] flex-shrink-0" />
                        <view class="flex-1">
                            <view class="text-[30rpx] font-bold text-[#1a1a2e]">AI 内容运营</view>
                            <view class="text-[22rpx] text-[#000000]/35 mt-[6rpx]">AI 生成 / 自动发布</view>
                        </view>
                    </view>
                    <view class="mt-[24rpx] grid grid-cols-2 gap-[16rpx]">
                        <view
                            class="bg-[#F8F9FF] rounded-[20rpx] px-[28rpx] py-[24rpx] flex items-center justify-between">
                            <view class="text-[22rpx] text-[#000000]/40 font-medium leading-relaxed">
                                <view>生成数</view>
                                <view>(条)</view>
                            </view>
                            <view class="text-[52rpx] font-bold text-[#1a1a2e]">{{
                                statistics.content_operation.count || 0
                            }}</view>
                        </view>
                        <view
                            class="bg-[#FFF8EE] rounded-[20rpx] px-[28rpx] py-[24rpx] flex items-center justify-between">
                            <view class="text-[22rpx] text-[#000000]/40 font-medium leading-relaxed">
                                <view>发布成功</view>
                                <view>(条)</view>
                            </view>
                            <view class="text-[52rpx] font-bold text-[#F88700]">{{
                                statistics.content_operation.publish_count || 0
                            }}</view>
                        </view>
                    </view>
                </view>

                <view
                    class="bg-white rounded-[28rpx] p-[36rpx] mb-[16rpx]"
                    style="box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.06)">
                    <view class="flex items-center gap-[20rpx]">
                        <image
                            :src="`${config.baseUrl}static/images/mp/mystic_orbit_2958.png`"
                            class="w-[80rpx] h-[80rpx] flex-shrink-0" />
                        <view class="flex-1">
                            <view class="text-[30rpx] font-bold text-[#1a1a2e]">AI 智能客服</view>
                            <view class="text-[22rpx] text-[#000000]/35 mt-[6rpx]">客服自动回复 / AI 接管私信</view>
                        </view>
                        <view
                            class="text-[#21ACC2] text-[22rpx] font-medium bg-[#E0FAFA] px-[20rpx] py-[10rpx] rounded-[100rpx]">
                            引流 {{ statistics.ai_customer.drainage || 0 }}%
                        </view>
                    </view>
                    <view class="mt-[24rpx] flex items-center justify-between text-[24rpx] mb-[14rpx]">
                        <text class="text-[#000000]/40"
                            >接待：<text class="text-[#1a1a2e] font-bold">{{
                                statistics.ai_customer.receive || 0
                            }}</text></text
                        >
                    </view>
                    <!-- <u-line-progress :percent="0" :height="10" :show-text="false" /> -->
                </view>

                <view class="bg-white rounded-[28rpx] p-[36rpx]" style="box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.06)">
                    <view class="flex items-center gap-[20rpx]">
                        <image
                            :src="`${config.baseUrl}static/images/mp/crystal_wave_9023.png`"
                            class="w-[80rpx] h-[80rpx] flex-shrink-0" />
                        <view class="flex-1">
                            <view class="text-[30rpx] font-bold text-[#1a1a2e]">AI 销售跟进</view>
                            <view class="text-[22rpx] text-[#000000]/35 mt-[6rpx]">自动拉群 / 自动加好友</view>
                        </view>
                        <view
                            class="text-[#FF6A57] text-[22rpx] font-medium bg-[#FFF0EE] px-[20rpx] py-[10rpx] rounded-[100rpx]">
                            拉群 {{ statistics.ai_sales.group || 0 }}
                        </view>
                    </view>
                    <view class="mt-[24rpx] flex items-center justify-between text-[24rpx] mb-[14rpx]">
                        <text class="text-[#000000]/40"
                            >接待：<text class="text-[#1a1a2e] font-bold">{{
                                statistics.ai_sales.add_friends || 0
                            }}</text></text
                        >
                    </view>
                    <!-- <u-line-progress :percent="statistics.ai_sales.rate" :height="10" :show-text="false" /> -->
                </view>
            </view>
        </view>

        <tabbar />
    </view>
</template>

<script setup lang="ts">
import config from "@/config";
import { getHomeDisplay } from "@/api/app";
import { getDeviceList as getDeviceListApi } from "@/api/device";
import { setFormData } from "@/utils/util";

const getDate = computed(() => {
    return uni.$u.timeFormat(new Date().getTime(), "yyyy.mm.dd");
});
const getWeek = computed(() => {
    const weekMap: Record<number, string> = {
        0: "星期日",
        1: "星期一",
        2: "星期二",
        3: "星期三",
        4: "星期四",
        5: "星期五",
        6: "星期六",
    };
    return `${weekMap[new Date().getDay()]}`;
});

const toPage = (url: string) => {
    uni.navigateTo({ url });
};

const deviceCount = ref(0);
const getDeviceCount = async () => {
    const { count } = await getDeviceListApi({ page_no: 1, page_size: 1 });
    deviceCount.value = count;
};

const statistics = reactive({
    today_task_count: 0,
    today_complete_task_count: 0,
    today_rate: 0,
    worker_device_count: 0,
    today_touch_number: "",
    today_expose_number: 0,
    seven_expose_number: "",
    seven_touch_number: 0,
    seven_intention_number: 0,
    ai_clues: {
        //智能获客
        touch_number: 0,
        expose_number: 0,
        rate: 0,
    },
    content_operation: {
        //内容运营
        count: 0,
        publish_count: 0,
    },
    ai_customer: {
        //智能客服
        receive: 0,
        drainage: 0,
    },
    ai_sales: {
        //ai销售跟进
        add_friends: 0,
        group: 0,
    },
});

const getHomeDisplayData = async () => {
    const data = await getHomeDisplay();
    setFormData(data, statistics);
};

onShow(() => {
    getDeviceCount();
    getHomeDisplayData();
});
</script>

<style scoped lang="scss"></style>
