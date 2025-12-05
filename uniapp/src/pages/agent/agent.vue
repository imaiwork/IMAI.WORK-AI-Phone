<template>
    <!-- 页面根元素，设置高度为屏幕高度，使用flex布局，列方向排列 -->
    <view class="h-screen flex flex-col page-bg">
        <!-- 自定义导航栏 -->
        <u-navbar
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }"
            :is-back="false">
            <!-- 当tab数量大于1时，显示tabs -->
            <view class="mx-4 w-full" v-if="tabList.length > 1">
                <u-tabs
                    :list="tabList"
                    bg-color="transparent"
                    :is-scroll="false"
                    :current="currTab"
                    @change="handleTabChange"></u-tabs>
            </view>
            <!-- 否则显示标题 -->
            <view class="mx-4 text-xl font-bold" v-else>AI智能体</view>
        </u-navbar>
        <!-- AI助理 (类型1) -->
        <template v-if="currType == 1">
            <!-- 搜索框 -->
            <view class="m-4 mt-2 relative z-10">
                <u-search
                    v-model="queryParams.name"
                    bg-color="#ffffff"
                    :show-action="false"
                    placeholder="请输入关键词"
                    @search="search"
                    @clear="clear"></u-search>
            </view>
            <!-- 内容区域 -->
            <view class="grow min-h-0 relative z-10">
                <view class="h-full flex">
                    <!-- 左侧分类菜单 -->
                    <view
                        class="w-[248rpx] h-full flex flex-col overflow-hidden flex-shrink-0 rounded-tr-[36rpx] bg-white">
                        <view class="grow min-h-0">
                            <scroll-view scroll-y class="h-full">
                                <!-- 一级分类 -->
                                <view
                                    v-for="(item, index) in optionsData.robotCate"
                                    class="robot-cate"
                                    :key="index"
                                    :class="[
                                        {
                                            'robot-cate-active': robotCateActiveMenu == index,
                                        },
                                        {
                                            'robot-cate-brother':
                                                robotSubCateIndex == item.sub_list.length - 1 &&
                                                isCurrMenu(item.sub_list),
                                        },
                                        {
                                            'robot-cate-first': robotSubCateIndex == 0 && isCurrMenu(item.sub_list),
                                        },
                                    ]"
                                    @click="handleRobotCate(index)">
                                    <view class="robot-cate-item">
                                        <view class="robot-cate-item-wrap">
                                            <view class="flex items-center gap-2 w-full">
                                                <view class="flex-1 text-[#6D6E70] text-xs font-bold">{{
                                                    item.name
                                                }}</view>
                                                <u-icon
                                                    :name="robotCateActiveMenu == index ? 'arrow-down' : 'arrow-right'"
                                                    :size="24"
                                                    color="#707173"></u-icon>
                                            </view>
                                            <view class="text-[20rpx] text-[#D0D0D0] mt-1">
                                                {{ item.sub_list.length }}
                                            </view>
                                        </view>
                                    </view>
                                    <!-- 二级分类 (展开时显示) -->
                                    <template v-if="robotCateActiveMenu == index">
                                        <view
                                            v-for="(subItem, subIndex) in item.sub_list"
                                            :key="`${index}-${subIndex}`"
                                            class="sub-robot"
                                            :class="{
                                                'sub-robot-active': currSubId == subItem.id,
                                                'sub-robot-last':
                                                    robotSubCateIndex != 0 &&
                                                    robotSubCateIndex - 1 == subIndex &&
                                                    isCurrMenu(item.sub_list),
                                            }"
                                            @click.stop="handleRobotSubCate(subIndex, subItem.id)">
                                            <view class="sub-robot-item">
                                                <view class="text-xs text-[#9A9A9C] line-clamp-1">
                                                    {{ subItem.name }}
                                                </view>
                                            </view>
                                        </view>
                                    </template>
                                </view>
                            </scroll-view>
                        </view>
                    </view>
                    <!-- 右侧机器人列表 -->
                    <view class="flex-1 flex flex-col min-h-0 overflow-hidden">
                        <view class="text-[20rpx] font-bold text-[#6D6E70] mt-2 mx-[24rpx] mb-4">
                            {{ total }}个智能体
                        </view>
                        <view class="grow relative">
                            <view class="flex justify-center items-center absolute w-full h-full" v-if="queryLoading">
                                <view class="loader"> </view>
                            </view>
                            <z-paging
                                ref="pagingRobotRef"
                                v-model="robots"
                                :auto="false"
                                :fixed="false"
                                :safe-area-inset-bottom="true"
                                @query="queryRobotList">
                                <view class="pl-[24rpx] pr-[16rpx] flex flex-col gap-4">
                                    <view
                                        v-for="(item, index) in robots"
                                        :key="index"
                                        class="bg-white p-[24rpx] rounded-[24rpx]"
                                        @click="handleRobot(item)">
                                        <view class="flex gap-2">
                                            <image
                                                :src="item.logo"
                                                lazy
                                                class="rounded-full w-[108rpx] h-[108rpx] flex-shrink-0"
                                                mode="aspectFill"></image>
                                            <view class="">
                                                <text class="font-bold mt-1 line-clamp-1">{{ item.name }}</text>
                                                <view class="text-[20rpx] mt-1 text-[#9C9C9E] line-clamp-2">
                                                    {{ item.description }}
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                                <template #empty>
                                    <view class="mx-4">
                                        <empty />
                                    </view>
                                </template>
                            </z-paging>
                        </view>
                    </view>
                </view>
            </view>
        </template>
        <!-- AI智能体 (类型2) -->
        <template v-if="currType == 2">
            <view class="mt-4">
                <scroll-view scroll-x>
                    <view class="flex gap-2 px-[32rpx]">
                        <view
                            v-for="item in agentCateLists"
                            class="text-[#959FAF] font-bold px-[24rpx] h-[64rpx] flex items-center rounded-full whitespace-nowrap"
                            :class="{ 'bg-primary !text-white': currAgentCateId === item.id }"
                            :key="item.id"
                            @click="handleAgentCateClick(item)">
                            <view>{{ item.name }}</view>
                        </view>
                    </view>
                </scroll-view>
            </view>
            <view class="grow min-h-0 mt-4">
                <z-paging
                    ref="pagingCozeRef"
                    v-model="agentList"
                    :fixed="false"
                    :safe-area-inset-bottom="true"
                    @query="handleQueryAgentList">
                    <view class="grid grid-cols-2 gap-4 px-[32rpx]">
                        <view
                            class="agent-item"
                            v-for="(item, index) in agentList"
                            :key="index"
                            @click="handleSelectAgent(item)">
                            <view
                                class="flex-shrink-0 h-[200rpx] w-full bg-no-repeat bg-center bg-cover relative"
                                :style="{ backgroundImage: `url(${item.bg_image})` }">
                                <view class="absolute -bottom-[40rpx] w-full flex justify-center">
                                    <view
                                        class="w-[108rpx] h-[108rpx] bg-white rounded-full p-[10rpx] flex justify-center">
                                        <image
                                            :src="item.image || item.avatar"
                                            class="w-full h-full rounded-full"
                                            mode="aspectFill"></image>
                                    </view>
                                </view>
                            </view>
                            <view class="mt-10 w-full px-3">
                                <view class="text-center line-clamp-1 font-bold">{{ item.name }}</view>
                                <view class="my-3 line-clamp-2 text-center text-[#737373] break-all">
                                    {{ item.intro || item.introduced }}
                                </view>
                            </view>
                        </view>
                    </view>
                    <template #empty>
                        <empty />
                    </template>
                </z-paging>
            </view>
        </template>
        <tabbar />
    </view>
</template>

<script lang="ts" setup>
import {
    robotCategory,
    robotLists,
    getCommonCozeAgentList,
    getCommonAgentList,
    getAgentCategoryList,
} from "@/api/agent";
import { useDictOptions } from "@/hooks/useDictOptions";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";

// 实例化app状态
const appStore = useAppStore();
// 实例化user状态
const userStore = useUserStore();

// 是否显示AI助理（机器人）的计算属性
const isShowRobot = computed(() => appStore.getIsShowRobot);

// Tab列表，默认为AI智能体
const tabList = ref<any[]>([{ name: "AI智能体", type: 2 }]);

// 当前选中的Tab索引
const currTab = ref(0);
// 当前选中的类型（1: AI助理, 2: AI智能体）
const currType = ref(tabList.value[0].type);

// 页面状态
const state = reactive({
    cate_id: "", // 分类ID
});

// AI助理（机器人）部分
const robotCateIndex = ref<number>(0); // 当前机器人分类索引
const robotCateActiveMenu = ref<number>(-1); // 当前激活的一级分类菜单索引
const pagingRobotRef = shallowRef(); // 机器人列表分页组件的引用
const robots = ref<any[]>([]); // 机器人列表数据
const total = ref<number>(0); // 机器人总数
const queryParams = reactive({
    // 机器人列表查询参数
    type: 3,
    scene_id: "",
    name: "",
});
const queryLoading = ref<boolean>(false); // 机器人列表加载状态

// AI智能体部分
const pagingCozeRef = shallowRef(); // 智能体列表分页组件的引用
const agentCateLists = ref<any[]>([]); // 智能体分类列表
const currAgentCateId = ref<any>(null); // 当前选中的智能体分类ID
const currAgentType = ref<any>(null); // 当前选中的智能体分类类型
const agentList = ref<any[]>([]); // 智能体列表数据

// 使用自定义hook获取机器人分类数据
const { optionsData } = useDictOptions<{
    robotCate: any[];
}>({
    robotCate: {
        api: robotCategory,
        params: {
            pageSize: 9999,
            pid: 0,
        },
        // 数据转换钩子
        transformData: (data) => {
            if (data.lists.length) {
                // 如果从其他页面跳转过来并传入了分类ID，则找到对应分类
                if (state.cate_id) {
                    robotCateIndex.value = data.lists.findIndex((item: any) => item.id == state.cate_id);
                }
                // 重新加载机器人列表
                pagingRobotRef.value?.reload();
            }
            return data.lists;
        },
    },
});

const robotSubCateIndex = ref<number>(-1); // 当前激活的二级分类菜单索引
const currSubId = ref<number>(); // 当前选中的二级分类ID

// 用户是否登录的计算属性
const isLogin = computed(() => userStore.isLogin);

/**
 * 切换顶部Tab
 * @param {number} index - Tab的索引
 */
const handleTabChange = async (index: number) => {
    currType.value = tabList.value[index].type;
    currTab.value = index;
    // 如果切换到AI助理tab，延迟加载数据以确保组件渲染完成
    if (currTab.value == 1) {
        setTimeout(() => {
            pagingRobotRef.value?.reload();
        }, 300);
    }
};

/**
 * 搜索机器人
 */
const search = async () => {
    pagingRobotRef.value?.reload();
};

/**
 * 清除搜索关键词并重新加载
 */
const clear = async () => {
    pagingRobotRef.value?.reload();
};

/**
 * 查询机器人列表（由z-paging调用）
 * @param {number} pageNo - 页码
 * @param {number} pageSize - 每页数量
 */
const queryRobotList = async (pageNo: number, pageSize: number) => {
    try {
        const { lists = [], count } = await robotLists({
            page_size: pageSize,
            page_no: pageNo,
            ...queryParams,
        });
        total.value = count; // 更新总数
        pagingRobotRef.value?.complete(lists); // 请求成功，更新分页数据
        queryLoading.value = false;
    } catch (error) {
        console.error("查询机器人列表失败:", error);
        queryLoading.value = false;
        pagingRobotRef.value?.complete(false); // 请求失败
    }
};

/**
 * 点击一级机器人分类
 * @param {number} index - 分类索引
 */
const handleRobotCate = (index: number) => {
    // 如果点击的是当前已展开的分类，则折叠
    if (index == robotCateActiveMenu.value) {
        robotCateActiveMenu.value = -1;
        return;
    }
    robotCateActiveMenu.value = index;
};

/**
 * 点击二级机器人分类
 * @param {number} index - 二级分类索引
 * @param {number} id - 二级分类ID
 */
const handleRobotSubCate = (index: number, id: number) => {
    // 防止重复点击
    if (index == robotSubCateIndex.value && id == currSubId.value) {
        return;
    }
    currSubId.value = id;
    robotSubCateIndex.value = index;
    queryLoading.value = true; // 显示加载动画

    const currSubLists = optionsData.robotCate[robotCateActiveMenu.value]?.sub_list;
    queryParams.scene_id = currSubLists[index]?.id;
    pagingRobotRef.value?.reload(); // 重新加载列表
};

/**
 * 检查当前二级分类是否属于给定的一级分类列表
 * @param {any[]} lists - 某个一级分类下的二级分类列表
 * @returns {boolean}
 */
const isCurrMenu = (lists: any[]) => {
    return lists.some((item) => item.id == currSubId.value);
};

/**
 * 点击机器人项，跳转到聊天页面
 * @param {any} data - 机器人数据
 */
const handleRobot = (data: any) => {
    uni.$u.route({
        url: "/packages/pages/robot_chat/robot_chat",
        params: {
            id: data.id,
        },
    });
};

/**
 * 获取智能体分类列表
 */
const getAgentCategoryListData = async () => {
    try {
        const { lists } = await getAgentCategoryList({
            page_size: 25000,
        });
        agentCateLists.value = lists || [];
        // 默认选中第一个分类
        if (agentCateLists.value.length > 0) {
            currAgentCateId.value = agentCateLists.value[0].id;
            currAgentType.value = agentCateLists.value[0].type;
        }
    } catch (error) {
        console.error("获取智能体分类失败:", error);
    }
};

/**
 * 点击智能体分类
 * @param {any} item - 分类项数据
 */
const handleAgentCateClick = (item: any) => {
    currAgentCateId.value = item.id;
    currAgentType.value = item.type;
    pagingCozeRef.value?.reload(); // 重新加载智能体列表
};

/**
 * 点击智能体项
 * @param {any} item - 智能体数据
 */
const handleSelectAgent = (item: any) => {
    // 判断是否登录
    if (!isLogin.value) {
        uni.$u.route({ url: "/pages/login/login" });
        return;
    }
    // 根据智能体类型跳转到不同页面
    if (currAgentType.value == 1) {
        // 通用智能体
        uni.$u.route({
            url: "/pages/index/index",
            params: {
                agent_name: item.name,
                agent_id: item.id,
                agent_logo: item.image,
            },
        });
    } else {
        // Coze等其他类型智能体
        uni.$u.route({
            url: "/packages/pages/coze_chat/coze_chat",
            params: {
                id: item.id,
                type: item.type,
            },
        });
    }
};

/**
 * 查询智能体列表（由z-paging调用）
 * @param {number} page_no - 页码
 * @param {number} page_size - 每页数量
 */
const handleQueryAgentList = async (page_no: number, page_size: number) => {
    try {
        const isType1 = currAgentType.value === 1;
        // 根据类型选择不同的API和参数
        const api = isType1 ? getCommonAgentList : getCommonCozeAgentList;
        const params = {
            page_no,
            page_size,
            source: 0,
            ...(isType1
                ? { cate_id: currAgentCateId.value }
                : { agent_cate_id: currAgentCateId.value, type: currAgentType.value === 2 ? 1 : 2 }),
        };
        // @ts-ignore
        const response = await api(params);
        pagingCozeRef.value?.complete(response.lists || []); // 请求成功，更新分页数据
    } catch (error) {
        console.error("查询智能体列表失败:", error);
        pagingCozeRef.value?.complete(false); // 请求失败
    }
};

// 监听isShowRobot变化，动态添加或移除"AI助理"tab
watch(
    () => isShowRobot.value,
    (newVal) => {
        // 移除已存在的 "AI助理" tab，防止重复添加
        const assistantTabIndex = tabList.value.findIndex((tab) => tab.type === 1);
        if (assistantTabIndex > -1) {
            tabList.value.splice(assistantTabIndex, 1);
        }

        // 如果配置显示，则在首位添加 "AI助理" tab
        if (newVal == "1") {
            tabList.value.unshift({ name: "AI助理", type: 1 });
        }

        // 更新当前tab和类型，确保视图正确
        currTab.value = 0;
        currType.value = tabList.value[0].type;
    },
    { immediate: true } // 立即执行一次
);

// onLoad生命周期，获取页面跳转时传递的参数
onLoad(({ id }: any) => {
    if (id) {
        state.cate_id = id;
    }
});

// onMounted生命周期，组件挂载后执行
onMounted(() => {
    getAgentCategoryListData();
});
</script>

<style lang="scss" scoped>
.robot-cate {
    @apply overflow-hidden gap-2 relative;
    &-active {
        &.robot-cate-first {
            .robot-cate-item {
                @apply relative z-40;
                .robot-cate-item-wrap {
                    @apply rounded-br-[36rpx];
                }
                &::after {
                    content: "";
                    @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6] z-10;
                }
            }
            .robot-cate-item {
                @apply rounded-br-[36rpx];
            }
        }
        &.robot-cate-brother {
            & + .robot-cate {
                .robot-cate-item-wrap {
                    @apply rounded-tr-[36rpx];
                }
                .robot-cate-item {
                    &::after {
                        content: "";
                        @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6] z-10;
                    }
                }
            }
        }
    }
}

.robot-cate-item-wrap {
    @apply relative z-30 px-[24rpx] py-3 bg-white;
}

.sub-robot-item {
    @apply w-full h-full flex items-center pl-[24rpx] relative z-10;
}

.sub-robot {
    @apply h-[100rpx] relative;
    // 当下一个元素是激活状态时的样式
    &-last {
        &::after {
            content: "";
            @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6];
        }
        .sub-robot-item {
            @apply rounded-br-[36rpx] bg-white overflow-hidden;
        }
        .sub-robot-item-bg {
            @apply bg-[#F5F6F6];
        }
    }
}

// 激活状态的子机器人项
.sub-robot-active {
    .sub-robot-item {
        @apply bg-[#F5F6F6] rounded-tl-[36rpx] rounded-bl-[36rpx];
    }

    // 激活项的下一个兄弟元素样式
    & + .sub-robot {
        &::after {
            content: "";
            @apply absolute top-0 left-0 w-full h-full bg-[#F5F6F6];
        }
        .sub-robot-item {
            @apply rounded-tr-[36rpx] bg-white overflow-hidden;
        }
        .sub-robot-item-bg {
            @apply bg-[#F5F6F6];
        }
    }
}
.agent-item {
    @apply bg-white rounded-[32rpx] overflow-hidden h-[430rpx];
    box-shadow: 0rpx 12rpx 24rpx rgba(0, 0, 0, 0.12);
}
</style>
