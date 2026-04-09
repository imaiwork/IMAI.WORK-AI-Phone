<template>
    <view class="min-h-screen bg-[#F4F7FA] pb-[200rpx]">
        <u-navbar :border-bottom="false" :background="{ background: '#F4F7FA' }" title="私域互动管家" title-bold>
        </u-navbar>

        <template v-if="loading">
            <view class="px-[30rpx] pt-2">
                <view v-for="i in 4" :key="i" class="bg-white rounded-[32rpx] p-5 mb-6 animate-pulse">
                    <view class="flex items-center gap-3 mb-4">
                        <view class="w-[48rpx] h-[48rpx] rounded-full bg-[#F3F4F6]"></view>
                        <view class="h-[30rpx] w-1/3 bg-[#F3F4F6] rounded-full"></view>
                    </view>
                    <view class="h-[120rpx] bg-[#F3F4F6] rounded-[20rpx]"></view>
                </view>
            </view>
        </template>

        <template v-else>
            <view class="px-[30rpx] pt-2">
                <view class="flex items-center gap-2 mb-5 px-2">
                    <u-icon name="account-fill" color="#9CA3AF" size="28"></u-icon>
                    <text class="text-[26rpx] text-[#b4b4b4]">当前IP：</text>
                    <text class="text-[26rpx] font-bold text-primary">{{ detail.persona_name }}</text>
                </view>

                <view class="mb-6">
                    <view class="flex items-center gap-2 mb-3 px-1">
                        <view class="w-[48rpx] h-[48rpx] rounded-full bg-[#E6F8F3] flex items-center justify-center">
                            <u-icon name="chat-fill" color="#00C08E" size="28"></u-icon>
                        </view>
                        <text class="text-[30rpx] font-extrabold text-[#212121]">加好友设置</text>
                    </view>
                    <view class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <view class="flex items-center justify-between mb-5">
                            <view class="flex flex-col">
                                <text class="text-[28rpx] font-bold text-[#212121]">客资线索库</text>
                                <text class="text-[22rpx] text-[#b4b4b4] mt-1">自动提取线索发起好友申请</text>
                            </view>
                            <view class="px-3 py-1.5 bg-[#E6F0FF] rounded-full flex items-center gap-1">
                                <text class="text-[24rpx] font-bold text-primary"
                                    >待添加 {{ formData.clue_count }}人</text
                                >
                            </view>
                        </view>
                        <view class="flex flex-col">
                            <text class="text-[28rpx] font-bold text-[#212121] mb-2">好友验证申请话术</text>
                            <view class="bg-[#00000005] rounded-[20rpx] px-4 py-2 border border-solid border-[#E8E8E8]">
                                <textarea
                                    v-model="formData.add_friend_script"
                                    class="w-full text-[#333] leading-[1.8] min-h-[120rpx]"
                                    placeholder="请输入好友验证申请话术..."
                                    :maxlength="200"
                                    :auto-height="false" />
                            </view>
                        </view>
                    </view>
                </view>

                <view class="mb-6" v-if="false">
                    <view class="flex items-center gap-2 mb-3 px-1">
                        <view class="w-[48rpx] h-[48rpx] rounded-full bg-[#E6F8F3] flex items-center justify-center">
                            <u-icon name="weixin-fill" color="#00C08E" size="30"></u-icon>
                        </view>
                        <text class="text-[30rpx] font-extrabold text-[#212121]">新好友接待设置</text>
                    </view>
                    <view class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <text class="text-[28rpx] font-bold text-[#212121] mb-2 block">自动通过好友并打招呼</text>
                        <view class="bg-[#00000005] rounded-[20rpx] px-4 py-2 border border-solid border-[#E8E8E8]">
                            <textarea
                                class="w-full text-[#333] leading-[1.8] min-h-[140rpx]"
                                placeholder="请输入新好友接待话术..."
                                :maxlength="200"
                                :auto-height="false" />
                        </view>
                    </view>
                </view>

                <view class="mb-6">
                    <view class="flex items-center gap-2 mb-3 px-1">
                        <view
                            class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center"
                            style="background: linear-gradient(90deg, #ff9a9e 0%, #fecfef 50%, #a1c4fd 100%)">
                            <u-icon name="moments" color="#ffffff" size="30"></u-icon>
                        </view>
                        <text class="text-[30rpx] font-extrabold text-[#212121]">朋友圈互动设置</text>
                    </view>
                    <view class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <view class="flex items-center justify-between mb-6">
                            <view class="flex items-center gap-3">
                                <view
                                    class="w-[64rpx] h-[64rpx] rounded-[16rpx] bg-[#FFF0F0] flex items-center justify-center">
                                    <u-icon name="thumb-up-fill" color="#FF4D4F" size="32"></u-icon>
                                </view>
                                <view class="flex flex-col">
                                    <text class="text-[28rpx] font-bold text-[#212121]">自动点赞</text>
                                    <text class="text-[22rpx] text-[#b4b4b4] mt-0.5">浏览朋友圈自动点赞</text>
                                </view>
                            </view>
                            <u-switch
                                v-model="formData.is_like"
                                :active-value="1"
                                :inactive-value="0"
                                size="40"></u-switch>
                        </view>

                        <view class="flex items-center justify-between mb-6">
                            <view class="flex items-center gap-3">
                                <view
                                    class="w-[64rpx] h-[64rpx] rounded-[16rpx] bg-[#E6F8F3] flex items-center justify-center">
                                    <u-icon name="chat-fill" color="#00C08E" size="32"></u-icon>
                                </view>
                                <view class="flex flex-col">
                                    <text class="text-[28rpx] font-bold text-[#212121]">自动评论</text>
                                    <text class="text-[22rpx] text-[#b4b4b4] mt-0.5">客户动态下自动评论</text>
                                </view>
                            </view>
                            <u-switch
                                v-model="formData.is_comment"
                                :active-value="1"
                                :inactive-value="0"
                                size="40"></u-switch>
                        </view>

                        <view class="flex flex-col">
                            <text class="text-[28rpx] font-bold text-[#212121] mb-3">朋友圈评论设置</text>
                            <view class="bg-[#00000005] p-1 rounded-[20rpx] flex items-center mb-5 relative">
                                <view
                                    class="flex-1 py-2 rounded-[16rpx] text-center transition-all duration-300 relative z-10"
                                    :class="formData.comment_method === 1 ? 'text-primary font-bold' : 'text-[#b4b4b4]'"
                                    @click="formData.comment_method = 1">
                                    <text class="text-[26rpx]">AI拟人评论</text>
                                </view>
                                <view
                                    class="flex-1 py-2 rounded-[16rpx] text-center transition-all duration-300 relative z-10"
                                    :class="formData.comment_method === 2 ? 'text-primary font-bold' : 'text-[#b4b4b4]'"
                                    @click="formData.comment_method = 2">
                                    <text class="text-[26rpx]">固定话术随机</text>
                                </view>
                                <view
                                    class="absolute top-1 bottom-1 w-[calc(50%-4rpx)] bg-white rounded-[16rpx] shadow-sm transition-transform duration-300 ease-out"
                                    :style="{
                                        transform: `translateX(${formData.comment_method === 1 ? '4rpx' : '100%'})`,
                                    }">
                                </view>
                            </view>

                            <view
                                v-if="formData.comment_method === 1"
                                class="bg-[#F8F9FD] rounded-[20rpx] p-4 border border-gray-100">
                                <view class="flex items-center justify-between mb-2">
                                    <view class="flex items-center gap-1.5">
                                        <u-icon name="checkmark-circle-fill" color="#0065fb" size="32"></u-icon>
                                        <text class="text-[28rpx] font-bold text-[#212121]">评论机器人提示词</text>
                                    </view>
                                    <view class="flex items-center text-primary" @click="openCommentPrompt">
                                        <text class="text-[24rpx] mr-0.5 font-medium">修改</text>
                                        <u-icon name="arrow-right" size="22"></u-icon>
                                    </view>
                                </view>
                                <text class="text-[24rpx] text-[#b4b4b4] leading-relaxed block">
                                    自动读取客户朋友圈图文内容，结合当前 IP
                                    <text class="text-primary font-bold">【{{ detail.persona_name }}】</text> 人设，由
                                    AI 实时生成千人千面的专属评论。
                                </text>
                            </view>

                            <view v-if="formData.comment_method === 2" class="flex flex-wrap gap-2">
                                <view
                                    v-for="(item, index) in formData.comment_speech"
                                    :key="index"
                                    class="bg-[#F8F9FD] border border-solid border-[#ececec] rounded-[20rpx] px-3 py-2 flex items-center gap-2 max-w-full active:scale-[0.98] transition-transform"
                                    @click="handleEditCommentContent(index)">
                                    <text class="text-[26rpx] text-[#424242] flex-1 truncate">{{ item }}</text>
                                    <view
                                        class="w-[36rpx] h-[36rpx] rounded-full bg-[#E8E8E8] flex items-center justify-center flex-shrink-0"
                                        @click.stop="handleCommentContentDelete(index)">
                                        <u-icon name="close" color="#9CA3AF" size="20"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="bg-[#E6F0FF] border border-solid border-[#0065fb]/20 rounded-[20rpx] px-4 py-2 flex items-center justify-center gap-1"
                                    @click="handleEditCommentContent(-1)">
                                    <u-icon name="plus" color="#0065fb" size="24"></u-icon>
                                    <text class="text-primary font-bold text-[26rpx]">添加话术</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>

                <view class="mb-6">
                    <view class="flex items-center gap-2 mb-3 px-1">
                        <view
                            class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center"
                            style="background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%)">
                            <u-icon name="setting-fill" color="#ffffff" size="28"></u-icon>
                        </view>
                        <text class="text-[30rpx] font-extrabold text-[#212121]">防封控与频率限制</text>
                    </view>
                    <view class="bg-white rounded-[32rpx] p-5 shadow-[0_8rpx_24rpx_rgba(0,0,0,0.02)]">
                        <view class="bg-[#E6F0FF]/60 rounded-[16rpx] p-3 mb-6">
                            <text class="text-[24rpx] text-primary leading-relaxed">
                                已开启"拟人随机停顿"。每次互动后，系统将随机停留
                                30秒~2分钟，模拟真人浏览行为，降低风控风险。
                            </text>
                        </view>

                        <view class="flex items-center justify-between mb-4">
                            <text class="text-[28rpx] font-bold text-[#212121]">每天互动人数(仅互动当天)</text>
                            <text class="text-[32rpx] font-extrabold text-primary">{{ formData.number }}人</text>
                        </view>
                        <view class="mb-2">
                            <u-slider
                                v-model="formData.number"
                                min="1"
                                max="30"
                                inactive-color="#E5E7EB"
                                block-width="36"></u-slider>
                        </view>
                        <view class="flex items-center justify-between">
                            <text class="text-[22rpx] text-[#b4b4b4]">保守 (防封)</text>
                            <text class="text-[22rpx] text-[#b4b4b4]">激进 (易封)</text>
                        </view>
                    </view>
                </view>
            </view>

            <view
                class="fixed bottom-0 left-0 right-0 bg-white px-5 pt-3 pb-[calc(20rpx+env(safe-area-inset-bottom))] z-50">
                <u-button
                    type="primary"
                    shape="circle"
                    :ripple="true"
                    :loading="saving"
                    :custom-style="{
                        height: '96rpx',
                        fontSize: '30rpx',
                        fontWeight: '900',
                        border: 'none',
                        boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                    }"
                    @click="handleSaveConfig">
                    保存配置
                </u-button>
                <view class="text-center mt-2.5">
                    <text class="text-[22rpx] text-[#b4b4b4]">配置自动同步至关联设备</text>
                </view>
            </view>
        </template>
    </view>

    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        title="固定话术"
        @confirm="handleKeywordsEditConfirm" />
</template>

<script setup lang="ts">
import { getPersonDetail, getInteractionConfig, updateInteractionConfig } from "@/api/person";
import { ListenerTypeEnum } from "@/ai_modules/person/enums";
import KeywordsEdit from "@/ai_modules/person/components/keywords-edit/keywords-edit.vue";
import { setFormData } from "@/utils/util";
import { useEventBusManager } from "@/hooks/useEventBusManager";

// ─── 类型定义 ─────────────────────────────────────────────────────
interface PersonDetail {
    id: string;
    industryType: string;
    persona_name?: string;
}

interface FormData {
    clue_count: number;
    add_friend_script: string;
    is_like: number;
    is_comment: number;
    comment_method: number;
    comment_speech: string[];
    number: number;
    comment_robot_prompt: string;
}

// ─── 页面状态 ─────────────────────────────────────────────────────
const { on } = useEventBusManager();

const loading = ref<boolean>(true);
const saving = ref<boolean>(false);
const personId = ref<string>("");

const detail = ref<PersonDetail>({ id: "", industryType: "" });

const formData = reactive<FormData>({
    clue_count: 0,
    add_friend_script: "",
    is_like: 0,
    is_comment: 0,
    comment_method: 1,
    comment_speech: [],
    number: 15,
    comment_robot_prompt: "",
});

// ─── 评论提示词跳转 ───────────────────────────────────────────────
const openCommentPrompt = (): void => {
    uni.$u.route({
        url: "/ai_modules/person/pages/prompt_set/prompt_set",
        params: {
            type: ListenerTypeEnum.CIRCLE_INTERACT_PROMPT,
            prompt: formData.comment_robot_prompt,
        },
    });
};

// ─── 固定话术编辑 ─────────────────────────────────────────────────
const keywordsEditRef = shallowRef<InstanceType<typeof KeywordsEdit>>();
const showKeywordsEdit = ref<boolean>(false);
const keywordsEditIndex = ref<number>(-1);

const handleEditCommentContent = (index: number): void => {
    keywordsEditIndex.value = index;
    keywordsEditRef.value?.setFormData(index > -1 ? formData.comment_speech[index] : "");
    showKeywordsEdit.value = true;
};

const handleCommentContentDelete = (index: number): void => {
    formData.comment_speech.splice(index, 1);
};

const handleKeywordsEditConfirm = (value: string): void => {
    if (!value.trim()) return;
    if (keywordsEditIndex.value === -1) {
        formData.comment_speech.push(value);
    } else {
        formData.comment_speech[keywordsEditIndex.value] = value;
    }
    keywordsEditIndex.value = -1;
    showKeywordsEdit.value = false;
};

// ─── 表单校验 ─────────────────────────────────────────────────────
const validateForm = (): string | null => {
    if (!formData.add_friend_script.trim()) return "请输入好友验证申请话术";
    if (formData.comment_method === 1 && !formData.comment_robot_prompt.trim()) {
        openCommentPrompt();
        return "请前往评论机器人页面，设置评论机器人提示词";
    }
    if (formData.comment_method === 2 && formData.comment_speech.length === 0) {
        return "请至少添加一条固定话术";
    }
    return null;
};

// ─── 保存配置 ─────────────────────────────────────────────────────
const handleSaveConfig = async (): Promise<void> => {
    const errMsg = validateForm();
    if (errMsg) {
        uni.showToast({ title: errMsg, icon: "none", duration: 2000 });
        return;
    }
    if (saving.value) return; // 防重复提交
    try {
        saving.value = true;
        formData.is_like = formData.is_like ? 1 : 0;
        formData.is_comment = formData.is_comment ? 1 : 0;
        await updateInteractionConfig({ persona_id: personId.value, ...formData });
        uni.showToast({ title: "保存成功", icon: "none", duration: 3000 });
        setTimeout(() => uni.navigateBack(), 1500);
    } catch (error: unknown) {
        const msg = typeof error === "string" ? error : "保存失败，请重试";
        uni.showToast({ title: msg, icon: "none", duration: 3000 });
    } finally {
        saving.value = false;
    }
};

// ─── 初始化数据 ───────────────────────────────────────────────────

const getDetail = async (): Promise<void> => {
    loading.value = true;
    try {
        const [detailResult, configResult] = await Promise.allSettled([
            getPersonDetail({ id: personId.value }),
            getInteractionConfig({ persona_id: personId.value }),
        ]);

        if (detailResult.status === "fulfilled") {
            detail.value = detailResult.value ?? { id: "", industryType: "" };
        } else {
            detail.value = { id: "", industryType: "" };
        }

        if (configResult.status === "fulfilled") {
            setFormData(configResult.value, formData);
        }
    } finally {
        loading.value = false;
    }
};

// ─── 生命周期 ─────────────────────────────────────────────────────
onLoad((options: any) => {
    personId.value = options.id;
    getDetail();

    on("confirm", (res: { type: ListenerTypeEnum; data: string }) => {
        if (res.type === ListenerTypeEnum.CIRCLE_INTERACT_PROMPT) {
            formData.comment_robot_prompt = res.data;
        }
    });
});
</script>
