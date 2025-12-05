<template>
    <view class="h-screen flex flex-col relative">
        <view class="index-bg"></view>
        <view class="relative z-30">
            <u-navbar
                :border-bottom="false"
                :is-fixed="false"
                :is-custom-back-icon="true"
                :background="{
                    background: 'transparent',
                }">
                <template #custom-back-icon>
                    <view @click="handleBack">
                        <u-icon name="/static/images/icons/back_black.svg" size="32"></u-icon>
                    </view>
                </template>
                <view class="flex items-center gap-2">
                    <view
                        class="leading-[0] w-[64rpx] h-[64rpx] p-[6rpx] rounded-[10rpx] border border-solid border-[#E5E6F3] bg-white flex-shrink-0">
                        <image :src="detail.logo" class="w-full h-full rounded-[10rpx]"></image>
                    </view>
                    <view>
                        <view class="font-bold text-[28rpx] line-clamp-1">{{ detail.name }}</view>
                        <view class="text-[20rpx] text-[#969EA9] line-clamp-1">{{ detail.description }}</view>
                    </view>
                </view>
            </u-navbar>
        </view>
        <view class="grow min-h-0 relative z-10">
            <chat-scroll-view
                ref="chattingRef"
                is-staff
                :is-stop="isStopChat"
                :content-list="chatContentList"
                :send-disabled="isReceiving"
                :tokens="getSceneTokens"
                @close="chatClose"
                @add-session="addSession"
                @content-post="contentPost"
                @confirm-knb="confirmKnb">
                <template #content>
                    <view
                        class="flex items-center justify-center flex-col h-full w-full px-4"
                        v-if="chatContentList.length == 0">
                        <view class="mb-3">
                            <image :src="detail.logo" v-if="detail.logo" class="w-[96rpx] h-[96rpx] rounded-full" />
                        </view>
                        <view class="text-center text-2xl font-semibold">
                            {{ detail.name }}
                        </view>
                        <view class="text-sm text-center mt-1">
                            {{ detail.description }}
                        </view>
                        <view class="mt-4 mx-4 flex">
                            <view class="flex flex-wrap gap-4">
                                <view class="w-[47%]" v-for="(item, index) in detail.preliminary_ask" :key="index">
                                    <view
                                        v-if="item.value"
                                        class="w-full h-full bg-white p-4 text-sm rounded-2xl"
                                        @click="contentPost(item.value)">
                                        <div class="line-clamp-5 break-all">
                                            {{ item.value }}
                                        </div>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </template>
            </chat-scroll-view>
        </view>
        <dragon-button :size="96" :y-edge="150" :x-edge="5">
            <view
                class="leading-[0] h-[96rpx] w-[96rpx] flex items-center justify-center bg-white rounded-full"
                @click="openSlider">
                <image src="@/packages/static/icons/msg.svg" class="w-[58rpx] h-[58rpx]"></image>
            </view>
        </dragon-button>
    </view>
    <Sidebar ref="sidebarRef" :form-list="getFormLists" :title="detail.name" @success="getSliderParams" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script lang="ts" setup>
import { robotDetail } from "@/api/agent";
import { useUserStore } from "@/stores/user";
import { chatRobotSendTextStream, getChatLog } from "@/api/chat";
import Sidebar from "./components/sidebar.vue";
import { TokensSceneEnum, KnbTypeEnum } from "@/enums/appEnums";

const sidebarRef = shallowRef<InstanceType<typeof Sidebar>>();

const userStore = useUserStore();
const { userTokens, isLogin } = toRefs(userStore);
const getSceneTokens = userStore.getTokenByScene(TokensSceneEnum.SCENE_CHAT)?.score;

const rechargePopupRef = ref();

const detail = reactive<Record<string, any>>({
    id: "",
    name: "",
    logo: "",
    form_info: "",
    description: "",
    template_info: {},
    preliminary_ask: [],
    assistants_id: "",
});

const handleBack = () => {
    uni.navigateBack();
};

const confirmKnb = (val: any) => {
    const { type, data } = val;
    if (type == KnbTypeEnum.RAG) {
        chatPostParams.indexid = data.index_id;
        chatPostParams.rerank_min_score = data.rerank_min_score;
    } else if (type == KnbTypeEnum.VECTOR) {
        chatPostParams.kb_id = data.id;
        chatPostParams.indexid = undefined;
        chatPostParams.rerank_min_score = undefined;
    }
};

const chattingRef = shallowRef();
const isReceiving = ref(false);
const isStopChat = ref(false);
const chatContentList = ref<any[]>([]);
const taskId = ref<any>("");
const fileLists = ref<any[]>([]);
const fileLimit = 9;

const chatLogParams = reactive<any>({
    page_no: 1,
    page_size: 1500,
});

const chatPostParams = reactive<any>({
    indexid: "",
    rerank_min_score: "",
});

const getSliderParams = (data: any) => {
    if (userTokens.value <= 0) {
        rechargePopupRef.value?.open();
        return;
    }
    fileLists.value = data.file || [];
    contentPost();
};

// 获取聊天记录
const getChatList = async () => {
    try {
        const data = await getChatLog({
            ...chatLogParams,
            assistant_id: detail.id,
            task_id: taskId.value,
        });
        const transformData = data?.map((item: any) => {
            if (item.type == 1) {
                return {
                    ...item,
                    // fileLists:
                    // 	item.file_urls && item.file_urls.length
                    // 		? item.file_urls.map((val: string) => ({
                    // 				url: val,
                    // 				name: val.split("/").pop(),
                    // 		  }))
                    // 		: [],
                };
            } else {
                return {
                    ...item,
                    consume_tokens: item.tokens_info || {},
                };
            }
        });
        chatContentList.value = transformData;
    } catch (err) {}
};

let streamReader: any = null;

const contentPost = async (userInput?: any, isNewChat: boolean = false) => {
    if (!isLogin.value) {
        uni.$u.route({
            url: "/pages/login/login",
        });
        return;
    }
    if (userTokens.value <= 0) {
        uni.$u.toast("算力不足，请充值！");
        rechargePopupRef.value?.open();
        return;
    }
    if (isReceiving.value) return;
    isReceiving.value = true;
    if (!isNewChat) {
        chatContentList.value.push({
            type: 1,
            message: userInput,
            fileLists: fileLists.value,
        });
    }

    const result = reactive({
        type: 2,
        loading: true,
        reply: "",
        is_reasoning_finished: false,
        reasoning_content: "",
        consume_tokens: {},
        error: "",
    });
    chatContentList.value.push(result);
    try {
        await chatRobotSendTextStream(
            {
                message: userInput || "",
                message_ext: JSON.stringify(sidebarRef.value?.formData),
                assistant_id: detail.id,
                ...chatPostParams,
            },
            {
                onstart(reader) {
                    streamReader = reader;
                    isStopChat.value = true;
                },
                onmessage(value) {
                    value
                        .trim()
                        .split("data:")
                        .forEach((text, index) => {
                            if (text !== "") {
                                try {
                                    const dataJson = JSON.parse(text);
                                    const { object, content, task_id, reasoning_content, usage } = dataJson;
                                    if ((content || reasoning_content) && object === "loading") {
                                        if (reasoning_content) {
                                            result.is_reasoning_finished = false;
                                            result.reasoning_content += reasoning_content;
                                        } else {
                                            result.is_reasoning_finished = true;
                                            result.reply += content;
                                        }
                                    }
                                    if (object === "finished") {
                                        result.loading = false;
                                        result.consume_tokens = {
                                            ...usage,
                                        };
                                        if (!taskId.value) {
                                            taskId.value = task_id;
                                        }
                                        return;
                                    }
                                    chattingRef.value.scrollToBottom();
                                } catch (error) {}
                            }
                        });
                },
                onclose() {
                    result.loading = false;
                    resetChat();
                    userStore.getUser();
                    setTimeout(async () => {
                        chattingRef.value.scrollToBottom();
                    }, 600);
                },
            }
        );
    } catch (error: any) {
        if (error.errno == 600004) {
            result.reply = "用户已停止内容生成";
        } else {
            result.reply = error || "发生错误";
            uni.$u.toast(error);
        }
        result.loading = false;
        resetChat();
    }
    nextTick(() => {
        chattingRef.value.scrollToBottom();
    });
};

const openSlider = async () => {
    if (isReceiving.value) {
        uni.$u.toast("当前有内容生成，请稍后再试");
        return;
    }
    sidebarRef.value?.open();
};

const addSession = () => {
    if (!taskId.value) {
        uni.$u.toast("当前会话已经是最新的了");
        return;
    }
    taskId.value = "";
    chatContentList.value = [];
    resetChat();
    contentPost("你好", true);
};

const resetChat = () => {
    fileLists.value = [];
    isReceiving.value = false;
    isStopChat.value = false;
};

const chatClose = () => {
    //#ifdef H5
    streamReader?.cancel();
    //#endif
    //#ifdef MP-WEIXIN
    streamReader?.abort();
    //#endif
    isReceiving.value = false;
    isStopChat.value = false;
};

const getDetail = async () => {
    const data = await robotDetail({
        assistant_id: detail.id,
    });
    Object.keys(detail).forEach((key) => {
        //@ts-ignore
        detail[key] = data[key];
    });
};

const getFormLists = computed(() => detail.template_info?.form || []);

const init = async () => {
    try {
        await getDetail();
        if (taskId.value) {
            uni.showLoading({
                title: "加载中",
            });
            await getChatList();
            setTimeout(() => {
                chattingRef.value?.scrollToBottom();
            }, 500);
            uni.hideLoading();
        }
        openSlider();
    } catch (error) {
        uni.hideLoading();
    }
};

onLoad(({ id, task_id }: any) => {
    detail.id = id;
    taskId.value = task_id;
    init();
});
</script>
