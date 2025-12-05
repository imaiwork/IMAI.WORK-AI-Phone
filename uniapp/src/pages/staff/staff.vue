<template>
    <view class="h-screen flex flex-col">
        <view
            class="h-[352rpx] bg-cover bg-center flex flex-col flex-shrink-0"
            :style="{ backgroundImage: `url(${config.baseUrl}static/images/staff_top_bg.png)` }">
            <view class="grow min-h-0">
                <view class="flex items-center gap-x-2 px-[64rpx]" :style="{ marginTop: `${getMarginTop}px` }">
                    <image :src="webSiteConfig.shop_logo" class="w-8 h-8 rounded-full"></image>
                    <text class="text-[#787979] font-bold">{{ webSiteConfig.shop_name }}</text>
                </view>
            </view>
            <view
                class="h-[84rpx] bg-[rgba(43,45,57,0.5)] flex-shrink-0 border-0 border-t-[1rpx] border-solid border-[#424353]">
                <view class="grid grid-cols-3 h-full relative">
                    <view v-for="(item, index) in tabs" :key="index" class="flex items-center gap-x-2">
                        <view class="tab-item" :class="{ active: currTabKey == item.key }" @click="handleTab(item.key)">
                            {{ item.label }}
                        </view>
                    </view>
                    <view class="tab-slider" :style="{ transform: `translateX(${tabIndex * 100}%)` }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[28rpx]">
            <scroll-view scroll-y class="h-full">
                <view class="px-[26rpx] pb-[100rpx]">
                    <view v-show="currTabKey == TabKey.AI_CUSTOMER">
                        <view class="grid items-center gap-[22rpx]" style="grid-template-columns: repeat(2, 1fr)">
                            <view class="sph-card">
                                <navigator
                                    url="/ai_modules/sph/pages/create_task/create_task"
                                    hover-class="none"
                                    class="w-full h-full">
                                    <view class="relative z-10">
                                        <view class="text-[#734207] font-bold text-[34rpx]">'视频号'获客</view>
                                        <view class="text-[#73420766] font-bold text-xs mt-1"> 无人工全自动 </view>
                                        <image
                                            src="/static/images/common/sph_icon.png"
                                            class="w-[44rpx] h-[44rpx] mt-[14rpx]"></image>
                                    </view>
                                    <view class="absolute right-[-100rpx] bottom-[-100rpx]">
                                        <image
                                            :src="`${config.baseUrl}static/images/sph_poster.png`"
                                            class="w-[406rpx]"
                                            mode="widthFix"></image>
                                    </view>
                                </navigator>
                            </view>
                            <view class="redbook-card" @click="toPage(AgentType.REDBOOK_TASK)">
                                <view class="text-[#734207] font-bold text-[34rpx]">'小红书'获客</view>
                                <view class="text-[#73420766] font-bold text-xs mt-1"> 即将解锁 </view>
                                <image
                                    src="/static/images/common/redbook_icon.png"
                                    class="w-[48rpx] h-[48rpx] absolute right-3 bottom-2"></image>
                            </view>
                            <view class="dy-card" @click="toPage(AgentType.DY_TASK)">
                                <view class="text-[#734207] font-bold text-[34rpx]">'抖音'获客</view>
                                <view class="text-[#73420766] font-bold text-xs mt-1"> 即将解锁 </view>
                                <image
                                    src="/static/images/common/dy_icon.png"
                                    class="w-[48rpx] h-[48rpx] absolute right-3 bottom-2"></image>
                            </view>
                        </view>
                        <view class="mt-[50rpx]">
                            <view class="text-[30rpx] font-bold"> AI矩阵运营 </view>
                            <view class="text-[#0000004d] mt-[6rpx] text-xs font-bold">
                                从创作到发布，多平台一键搞定
                            </view>
                            <view class="mt-[38rpx] grid grid-cols-2 gap-[22rpx]">
                                <view class="flex flex-col gap-y-[4rpx]">
                                    <view class="publish-video-line-1"></view>
                                    <view class="publish-video-line-2"></view>
                                    <view class="publish-video-card" @click="toPage(AgentType.VIDEO_TASK)">
                                        <view>
                                            <view class="text-[#702AFA] font-bold text-[34rpx]">发布视频</view>
                                            <view class="text-[rgba(112,42,250,0.4)] text-[22rpx] font-bold mt-1"
                                                >自动/定时发布</view
                                            >
                                        </view>
                                        <image src="/static/images/icons/video.svg" class="w-[44rpx] h-[44rpx]"></image>
                                    </view>
                                </view>
                                <view class="flex flex-col gap-y-[4rpx]">
                                    <view class="publish-img-line-1"></view>
                                    <view class="publish-img-line-2"></view>
                                    <view class="publish-img-card" @click="toPage(AgentType.IMG_TASK)">
                                        <view>
                                            <view class="text-[#069AB8] font-bold text-[34rpx]">发布图文</view>
                                            <view class="text-[rgba(6,154,184,0.4)] text-[22rpx] font-bold mt-1"
                                                >自动/定时发布</view
                                            >
                                        </view>
                                        <image src="/static/images/icons/img.svg" class="w-[44rpx] h-[44rpx]"></image>
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view class="mt-[50rpx]">
                            <view
                                class="utils-tips"
                                :style="{
                                    backgroundImage: `url(${config.baseUrl}static/images/staff_utils_tip_bg.png)`,
                                }">
                                超级AI创作引擎，重塑企业内容生产力
                            </view>
                            <view class="bg-white rounded-[20rpx] p-[24rpx] -mt-[30rpx]">
                                <view class="">
                                    <view
                                        v-for="(item, index) in utilsList"
                                        class="rounded-[20rpx] mb-2 px-[52rpx] py-[36rpx] flex items-center justify-between"
                                        :key="index"
                                        :class="[item.disabled ? 'bg-[#F8F8F8]' : 'bg-primary-light-9']"
                                        @click="toPage(item.key)">
                                        <view>
                                            <view class="flex items-center gap-x-2">
                                                <view
                                                    class="text-[34rpx] font-bold"
                                                    :class="[item.disabled ? 'text-[#00000080]' : 'text-primary']"
                                                    >{{ item.title }}</view
                                                >
                                                <view
                                                    v-if="item.disabled"
                                                    class="bg-[#F2E7C9] text-[#8F6B39] font-bold text-[18rpx] rounded-[10rpx] py-[4rpx] px-[10rpx]">
                                                    即将解锁
                                                </view>
                                            </view>
                                            <view
                                                class="text-[#0000004d] text-[22rpx] font-bold mt-1"
                                                :class="[item.disabled ? 'text-[#0000004d]' : 'text-[#0065fb66]']"
                                                >{{ item.desc }}</view
                                            >
                                        </view>
                                        <image :src="item.icon" class="w-[52rpx] h-[52rpx]"></image>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view v-show="currTabKey == TabKey.AI_SALES">
                        <view class="bg-white rounded-[20rpx] px-5 py-[32rpx]">
                            <view class="text-[30rpx] font-bold"> 运营统计 </view>
                            <view class="mt-[28rpx] text-[30rpx] font-bold">{{ getToady }}</view>
                            <view class="flex items-center gap-x-1 mt-[12rpx]">
                                <image src="/static/images/icons/device.svg" class="w-[28rpx] h-[28rpx]"></image>
                                <text class="text-[#0000004d] text-[22rpx] font-bold"
                                    >统计设备：{{ deviceCount }}个</text
                                >
                            </view>
                            <view class="grid grid-cols-4 gap-4 mt-[50rpx]">
                                <view v-for="(item, index) in salesStatsList" :key="index" class="py-2">
                                    <view class="text-[40rpx] font-bold text-center">{{ item.value }}</view>
                                    <view class="text-[#00000066] font-bold text-xs mt-[6rpx] text-center">{{
                                        item.title
                                    }}</view>
                                </view>
                            </view>
                        </view>
                        <view class="mt-[54rpx]">
                            <view class="text-[30rpx] font-bold mb-[28rpx]">私域运营</view>
                            <view class="grid grid-cols-2 gap-x-[22rpx]">
                                <view class="flex flex-col gap-y-[4rpx]">
                                    <view class="mx-auto w-[70%] h-[6rpx] bg-white rounded-full"></view>
                                    <view class="mx-auto w-[80%] h-[6rpx] bg-white rounded-full"></view>
                                    <view
                                        class="rounded-[20rpx] bg-white p-5 flex items-center justify-between gap-x-2"
                                        @click="toPage(AgentType.SEND_CONTENT)">
                                        <view>
                                            <view class="text-[30rpx] font-bold">群发内容</view>
                                            <view class="text-[22rpx] text-[#0000004d] font-bold mt-1"> 即将解锁 </view>
                                        </view>
                                        <image src="/static/images/icons/send.svg" class="w-[48rpx] h-[48rpx]"></image>
                                    </view>
                                </view>
                                <view class="flex flex-col gap-y-[4rpx]">
                                    <view class="mx-auto w-[70%] h-[6rpx] bg-white rounded-full"></view>
                                    <view class="mx-auto w-[80%] h-[6rpx] bg-white rounded-full"></view>
                                    <view
                                        class="rounded-[20rpx] bg-white p-5 flex items-center justify-between gap-x-2"
                                        @click="toPage(AgentType.SEND_CIRCLE)">
                                        <view>
                                            <view class="text-[30rpx] font-bold">发朋友圈</view>
                                            <view class="text-[22rpx] text-[#0000004d] font-bold mt-1"> 即将解锁 </view>
                                        </view>
                                        <image
                                            src="/static/images/icons/circle.svg"
                                            class="w-[48rpx] h-[48rpx]"></image>
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view class="mt-[54rpx]">
                            <view class="text-[30rpx] font-bold mb-[32rpx]">客户管理</view>
                            <view
                                class="rounded-[20rpx] bg-white p-[42rpx] mb-2 flex items-center justify-between gap-x-2"
                                @click="toPage(AgentType.FLOW_SETTING)">
                                <view class="flex items-center gap-x-3">
                                    <image src="/static/images/icons/flow.svg" class="w-[32rpx] h-[32rpx]"></image>
                                    <text class="text-[30rpx] font-bold">流程设置</text>
                                </view>
                                <u-icon name="arrow-right" color="#B2B2B2"></u-icon>
                            </view>
                            <view
                                class="rounded-[20rpx] bg-white p-[42rpx] mb-2 flex items-center justify-between gap-x-2"
                                @click="toPage(AgentType.TAG_SETTING)">
                                <view class="flex items-center gap-x-3">
                                    <image src="/static/images/icons/tags.svg" class="w-[32rpx] h-[32rpx]"></image>
                                    <text class="text-[30rpx] font-bold">标签设置</text>
                                </view>
                                <u-icon name="arrow-right" color="#B2B2B2"></u-icon>
                            </view>
                            <view
                                class="rounded-[20rpx] bg-white p-[42rpx] mb-2 flex items-center justify-between gap-x-2"
                                @click="toPage(AgentType.PRAISE_SETTING)">
                                <view class="flex items-center gap-x-3">
                                    <image src="/static/images/icons/praise.svg" class="w-[32rpx] h-[32rpx]"></image>
                                    <text class="text-[30rpx] font-bold">点赞设置</text>
                                </view>
                                <u-icon name="arrow-right" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                    </view>
                    <view v-show="currTabKey == TabKey.AI_INTERNAL">
                        <view class="flex flex-col gap-y-1 mb-[48rpx]">
                            <view
                                v-for="(item, index) in hotAgentList"
                                class="relative rounded-[48rpx] overflow-hidden transition-all duration-500"
                                :key="index"
                                :style="{
                                    height: currHotAgentKey == item.key ? '686rpx' : '316rpx',
                                }"
                                @click.stop="currHotAgentKey = currHotAgentKey == item.key ? '' : item.key">
                                <view
                                    class="absolute top-4 right-4 w-[28rpx] h-[28rpx] rounded-full z-20"
                                    @click.stop="handleAgent(item.key)">
                                </view>
                                <view class="bottom-[50rpx] right-8 z-20 absolute" v-if="currHotAgentKey != item.key">
                                    <view class="h-[40rpx] w-[140rpx]" @click.stop="handleAgent(item.key)"> </view>
                                </view>
                                <view
                                    class="bottom-[30rpx] w-full z-20 absolute px-[32rpx]"
                                    v-if="currHotAgentKey == item.key">
                                    <view class="h-[100rpx] rounded-full" @click.stop="handleAgent(item.key)"> </view>
                                </view>
                                <image
                                    :src="item.image"
                                    class="w-full h-full absolute top-0 left-0 transition-all duration-500"
                                    :style="{
                                        opacity: currHotAgentKey == item.key ? 0 : 1,
                                    }"></image>
                                <image
                                    :src="item.active_image"
                                    class="w-full h-full absolute top-0 left-0 transition-all duration-500"
                                    :style="{
                                        opacity: currHotAgentKey == item.key ? 1 : 0,
                                    }"></image>
                            </view>
                        </view>
                        <view class="mx-[32rpx]">
                            <view class="flex items-center rounded-full bg-[#00000008] gap-x-2 p-[6rpx]">
                                <u-icon name="/static/images/icons/tips.svg" :size="28"></u-icon>
                                <view class="opacity-30 text-xs"> 提示：电脑端提供更流畅的操作体验与更多高效工具 </view>
                            </view>
                            <view class="flex items-center gap-x-1 my-1">
                                <u-line />
                                <u-icon name="/static/images/icons/down.svg" :size="28"></u-icon>
                                <u-line />
                            </view>
                        </view>
                        <view class="flex flex-col gap-y-[16rpx]">
                            <view
                                v-for="(item, index) in commonAgentList"
                                :key="index"
                                class="rounded-[24rpx] flex items-center h-[136rpx] bg-white px-[32rpx] gap-x-[24rpx]"
                                @click="handleAgent(item.key)">
                                <image
                                    :src="`${config.baseUrl}static/images/${item.key}.svg`"
                                    class="w-[72rpx] h-[72rpx] flex-shrink-0"></image>
                                <view class="flex-1">
                                    <view class="flex items-center gap-x-2">
                                        <text class="text-[26rpx]">
                                            {{ item.name }}
                                        </text>
                                    </view>
                                    <view class="text-[22rpx] opacity-30 mt-1">{{ item.desc }}</view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <tabbar />
    </view>
    <u-popup v-model="showPcTips" mode="center" width="90%" border-radius="24" :show-title="false" :closeable="false">
        <view
            class="pc-tips-bg rounded-[24rpx] relative px-[40rpx] pb-[100%]"
            :style="{
                backgroundImage: `url(${config.baseUrl}static/images/pc_tips_bg.png)`,
            }">
            <view class="absolute top-4 right-4 z-[777]" @click="showPcTips = false">
                <image src="/static/images/icons/close.svg" class="w-[48rpx] h-[48rpx]"></image>
            </view>
            <view class="absolute left-0 top-0 w-full h-full z-[88]">
                <view class="text-white pt-[40rpx] text-center font-bold text-[40rpx]">
                    <view class="text-white text-center font-bold text-[40rpx]">
                        {{ webSiteConfig.shop_name }}全新升级,
                    </view>
                    <view> 为企业释放无限可能 </view>
                </view>
                <view class="flex justify-center items-center mt-[50rpx]">
                    <image :src="webSiteConfig.shop_logo" class="w-[100rpx] h-[100rpx] rounded-full"></image>
                </view>
            </view>
            <view class="w-full absolute top-[365rpx] left-0">
                <view class="w-[62%] mx-auto flex items-center">
                    <text class="pc-link line-clamp-1 w-full"> {{ domain }} </text>
                </view>
            </view>
            <view class="w-full absolute top-[455rpx] left-0">
                <view class="text-center w-[80%] mx-auto text-[#ffffff99]">
                    电脑端使用体验更富，我们为你开启更丰富的功能与操作空间！
                </view>
            </view>
            <view class="w-full absolute top-[535rpx] left-0 z-[777]">
                <view
                    class="flex justify-center items-center text-white font-bold mt-[40rpx]"
                    @click="
                        copy(domain);
                        showPcTips = false;
                    ">
                    复制链接
                </view>
            </view>
        </view>
    </u-popup>
</template>

<script lang="ts" setup>
import { getDeviceStatistics } from "@/api/device";
import { useAppStore } from "@/stores/app";
import { useCopy } from "@/hooks/useCopy";
import { formatNumberToWanOrYi } from "@/utils/util";
import config from "@/config";
import magicWandIcon from "@/static/images/icons/magic_wand.svg";
import paintingIcon from "@/static/images/icons/painting.svg";
import featherIcon from "@/static/images/icons/feather.svg";
import starIcon from "@/static/images/icons/star.svg";

enum TabKey {
    AI_CUSTOMER = "ai_customer",
    AI_SALES = "ai_sales",
    AI_INTERNAL = "ai_internal",
}

enum AgentType {
    AI_SPH = "sph",
    AI_DIGITAL_HUMAN = "digital_human",
    AI_INTERVIEW = "interview",
    AI_MEETING_SUMMARY = "meeting_minutes",
    AI_DERIVATIVE_WORK = "derivative_work",
    AI_PPT = "ppt",
    AI_CW_MARKETING = "cw_marketing",
    AI_PW_MARKETING = "pw_marketing",
    AI_DRAWING = "drawing",
    AI_LADDER_PLAYER = "ladder_player",
    AI_MIND_MAP = "mind_map",
    AI_WORD = "word",
    AI_SERVICE = "service",
    REDBOOK_TASK = "redbook_task",
    DY_TASK = "dy_task",
    VIDEO_TASK = "video_task",
    IMG_TASK = "img_task",
    IMG_CREATION = "img_creation",
    COPYWRITING = "copywriting",
    AUTO_ACCOUNT = "auto_account",
    VIDEO_CREATION = "video_creation",
    SEND_CONTENT = "send_content",
    SEND_CIRCLE = "send_circle",
    FLOW_SETTING = "flow_setting",
    TAG_SETTING = "tag_setting",
    PRAISE_SETTING = "praise_setting",
}

const appStore = useAppStore();
const domain = computed(() => appStore.config.domain);
const webSiteConfig = computed(() => appStore.getWebsiteConfig);

const tabs = [
    { label: "AI获客", key: TabKey.AI_CUSTOMER },
    { label: "AI销售", key: TabKey.AI_SALES },
    { label: "AI内务", key: TabKey.AI_INTERNAL },
];
const currTabKey = ref(TabKey.AI_CUSTOMER);
const tabIndex = computed(() => tabs.findIndex((item) => item.key === currTabKey.value));

const utilsList = [
    {
        title: "视频创作",
        desc: "AI数字人 / 形象声音1v1克隆 / 智能混剪",
        icon: magicWandIcon,
        disabled: false,
        key: AgentType.AI_DIGITAL_HUMAN,
    },
    {
        title: "图文创作",
        desc: "一站式满足各种场景的图片设计需求",
        icon: paintingIcon,
        disabled: true,
        key: AgentType.IMG_CREATION,
    },
    {
        title: "文案创作",
        desc: "高效内容生产助手，提升创作效率",
        icon: featherIcon,
        disabled: true,
        key: AgentType.COPYWRITING,
    },
    {
        title: "自动养号",
        desc: "模拟真人操控，安全稳定提升账号权重",
        icon: starIcon,
        disabled: true,
        key: AgentType.AUTO_ACCOUNT,
    },
];

const deviceCount = ref(0);

const salesStatsList = ref([
    {
        title: "社媒回复",
        value: 0,
        key: "social_media_reply_count",
    },
    {
        title: "获客人数",
        value: 0,
        key: "sph_clues_count",
    },
    {
        title: "私域回复",
        value: 0,
        key: "wechat_reply_count",
    },
    {
        title: "添加好友",
        value: 0,
        key: "wechat_add_count",
    },
    {
        title: "通过好友",
        value: 0,
        key: "wechat_through_friend_count",
    },
    {
        title: "点赞朋友圈",
        value: 0,
        key: "wechat_like_circle_count",
    },
    {
        title: "评论朋友圈",
        value: 0,
        key: "wechat_comment_circle_count",
    },
    {
        title: "发朋友圈",
        value: 0,
        key: "wechat_publish_circle_count",
    },
]);

const hotAgentList = ref([
    {
        key: AgentType.AI_SPH,
        name: "AI自动获客",
        title: "自动生成获客方向，提升转化效率。",
        desc: "您无需人工操作，即可实现线索自动化获取。系统将自动在视频号中刷选目标内容、提取主页联系方式，若未识别到微信号还能主动私信引导添加，实现从“曝光”到“加微信”的全流程自动化。助您节省人力、提升转化效率，实现低成本、高频次、高质量的精准获客。",
        image: `${config.baseUrl}static/images/staff_sph.png`,
        active_image: `${config.baseUrl}static/images/staff_sph_active.png`,
    },
    {
        key: AgentType.AI_DIGITAL_HUMAN,
        name: "智能数字人",
        title: "打造品牌数字形象，AI真人级体验。",
        desc: "打造专属于您的数字伙伴，AI数字人带来无限可能。随着它们不断学习与成长，将为您的业务、生活、娱乐带来全新体验。",
        image: `${config.baseUrl}static/images/staff_digital_human.png`,
        active_image: `${config.baseUrl}static/images/staff_digital_human_active.png`,
    },
    {
        key: AgentType.AI_MEETING_SUMMARY,
        name: "会议纪要",
        title: "省时、省力、更专业，智能纪要一步到位。",
        desc: "告别手动记录的繁琐，AI智能会议纪要，自动抓取关键信息，精准记录每一个细节。让您的工作更轻松，决策更高效！",
        image: `${config.baseUrl}static/images/staff_meeting_minutes.png`,
        active_image: `${config.baseUrl}static/images/staff_meeting_minutes_active.png`,
    },
    {
        key: AgentType.AI_INTERVIEW,
        name: "智能人事",
        title: "让管理更智慧，让企业更轻盈。",
        desc: "自动化（AI）人事系统，高效处理招聘等相关事务，节省时间、提高效率。轻松应对复杂流程，提升团队效率！",
        image: `${config.baseUrl}static/images/staff_personnel.png`,
        active_image: `${config.baseUrl}static/images/staff_personnel_active.png`,
    },
    {
        key: AgentType.AI_LADDER_PLAYER,
        name: "智能陪练",
        title: "将实战经验，萃取成标准训练模型。",
        desc: "通过智能（AI）模拟真实的客户沟通场景让您随时随地模拟各种销售情境，提升销售技巧与电话邀约的成功率。",
        image: `${config.baseUrl}static/images/staff_ladder_player.png`,
        active_image: `${config.baseUrl}static/images/staff_ladder_player_active.png`,
    },
]);

const commonAgentList = [
    {
        key: AgentType.AI_DERIVATIVE_WORK,
        name: "音乐创意",
        desc: "利用音乐及视频流量获客，轻松上热门",
    },
    {
        key: AgentType.AI_DRAWING,
        name: "智能设计",
        desc: "热门作图工具，根据内容自动几秒出稿",
    },
    {
        key: AgentType.AI_PPT,
        name: "制作 PPT",
        desc: "从空白页到专业演示，风格随心换，一键生成内容",
    },
    {
        key: AgentType.AI_CW_MARKETING,
        name: "企业微信销售",
        desc: "从等客户来到主动成交，从“人找人”到“系统找人”",
    },
    {
        key: AgentType.AI_MIND_MAP,
        name: "思维导图",
        desc: "内容自动梳理，逻辑一目了然",
    },
    {
        key: AgentType.AI_SERVICE,
        name: "智能客服",
        desc: "懂业务、懂用户，更懂你的服务流程",
    },
    {
        key: AgentType.AI_PW_MARKETING,
        name: "个人微信销售",
        desc: "让销售流程自动化，从此告别低效沟通",
    },
];
const currHotAgentKey = ref("");
const showPcTips = ref(false);

const getMarginTop = computed(() => {
    const { statusBarHeight } = uni.$u.sys();
    return statusBarHeight + uni.upx2px(46);
});

// 获取今天时间
const getToady = computed(() => uni.$u.timeFormat(new Date(), "yyyy-mm-dd"));

const { copy } = useCopy();

const handleTab = (key: TabKey) => {
    currTabKey.value = key;
    if (key === TabKey.AI_SALES) {
        getDeviceStatisticsFn();
    }
};

const handleAgent = (key: AgentType) => {
    switch (key) {
        case AgentType.AI_DIGITAL_HUMAN:
        case AgentType.AI_MEETING_SUMMARY:
        case AgentType.AI_LADDER_PLAYER:
        case AgentType.AI_INTERVIEW:
        case AgentType.AI_SPH:
            uni.$u.route({
                url: `/ai_modules/${key}/pages/index/index`,
            });
            return;
        default:
            showPcTips.value = true;
            return;
    }
};

const toPage = (type: AgentType) => {
    if (
        type === AgentType.REDBOOK_TASK ||
        type === AgentType.DY_TASK ||
        type === AgentType.SEND_CONTENT ||
        type === AgentType.SEND_CIRCLE ||
        type === AgentType.FLOW_SETTING ||
        type === AgentType.TAG_SETTING ||
        type === AgentType.PRAISE_SETTING
    ) {
        uni.$u.toast("开发中");
        return;
    }
    let url = "";
    switch (type) {
        case AgentType.VIDEO_TASK:
            url = "/ai_modules/device/pages/create_task/create_task?type=1";
            break;
        case AgentType.IMG_TASK:
            url = "/ai_modules/device/pages/create_task/create_task?type=2";
            break;
        case AgentType.AI_DIGITAL_HUMAN:
            url = "/ai_modules/digital_human/pages/index/index";
            break;
    }
    uni.navigateTo({
        url,
    });
};

const getDeviceStatisticsFn = async () => {
    const data = await getDeviceStatistics();
    deviceCount.value = data.device_count;
    salesStatsList.value.forEach((item) => {
        item.value = Number(formatNumberToWanOrYi(data[item.key] || 0));
    });
};
</script>

<style lang="scss" scoped>
.tab-item {
    @apply text-[#ffffff99] flex items-center justify-center h-full w-full font-bold relative z-10 transition-all duration-[800];
    &.active {
        @apply text-primary relative;
        &::before {
            content: "";
            transform: translateX(-50%);
            @apply absolute bottom-[15rpx] left-[50%] w-[28rpx] h-[4rpx] bg-primary rounded-full;
        }
    }
}

.tab-slider {
    @apply h-[96rpx] w-[33.33%] rounded-tl-[16rpx] rounded-tr-[16rpx] bg-[#F9FAFB] absolute top-[-12rpx] left-0 transition-all duration-500;
    &::after,
    &::before {
        content: "";
        @apply absolute bottom-0 w-[20rpx] h-[20rpx];
    }
    &::after {
        @apply left-[-19rpx];
        background: radial-gradient(circle at 0% 0%, transparent 20rpx, #f9fafb 21rpx);
    }
    &::before {
        @apply right-[-19rpx];
        background: radial-gradient(circle at 100% 0%, transparent 20rpx, #f9fafb 21rpx);
    }
}

.sph-card {
    background: linear-gradient(90deg, #f8e5c5 0%, #f0dcc4 100%);
    grid-area: 1 / 1 / 5 / 2;
    @apply rounded-[20rpx] p-[40rpx] h-[360rpx] relative overflow-hidden;
}

.redbook-card {
    background: linear-gradient(90deg, #faede6 0%, #f5e2d7 100%);
    grid-area: 1 / 2 / 3 / 3;
    @apply h-[170rpx] rounded-[20rpx] relative px-[36rpx] py-[28rpx];
}

.dy-card {
    background: linear-gradient(90deg, #faede6 0%, #f5e2d7 100%);
    grid-area: 3 / 2 / 5 / 3;
    @apply h-[170rpx] rounded-[20rpx] relative px-[36rpx] py-[28rpx];
}

.publish-video-card {
    background: linear-gradient(90deg, rgba(221, 234, 252, 1) 0%, rgba(226, 222, 255, 1) 100%);
    @apply rounded-[20rpx] px-[40rpx] py-[32rpx] flex items-center justify-between;
}

.publish-video-line-1 {
    @apply w-[70%] h-[6rpx] mx-auto rounded-full;
    background: linear-gradient(90deg, rgba(221, 234, 252, 1) 0%, rgba(226, 222, 255, 1) 100%);
}

.publish-video-line-2 {
    @apply w-[80%] h-[6rpx] mx-auto rounded-full;
    background: linear-gradient(90deg, rgba(221, 234, 252, 1) 0%, rgba(226, 222, 255, 1) 100%);
}

.publish-img-card {
    background: linear-gradient(90deg, rgba(220, 243, 249, 1) 0%, rgba(194, 234, 242, 1) 100%);
    @apply rounded-[20rpx] px-[40rpx] py-[32rpx] flex items-center justify-between;
}

.publish-img-line-1 {
    @apply w-[70%] h-[6rpx] mx-auto rounded-full;
    background: linear-gradient(90deg, rgba(220, 243, 249, 1) 0%, rgba(194, 234, 242, 1) 100%);
}

.publish-img-line-2 {
    @apply w-[80%] h-[6rpx] mx-auto rounded-full;
    background: linear-gradient(90deg, rgba(220, 243, 249, 1) 0%, rgba(194, 234, 242, 1) 100%);
}

.utils-tips {
    @apply h-[120rpx] rounded-[20rpx] px-[36rpx] text-[#00000080] font-bold pt-[30rpx] bg-no-repeat  bg-cover;
}

.hot-card {
    @apply rounded-[48rpx] h-[316rpx];
    background-repeat: no-repeat;
}
.pc-tips-bg {
    background-size: 100% 100%;
}
.pc-link {
    @apply text-[26rpx] text-white text-center;
    opacity: 0.6;
    background: linear-gradient(90deg, #0065fb 0%, rgba(0, 101, 251, 0.5) 100%);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>
