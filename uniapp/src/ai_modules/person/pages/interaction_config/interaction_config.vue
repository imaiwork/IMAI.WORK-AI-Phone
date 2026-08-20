<template>
    <view class="min-h-screen bg-[#F5F7FA] pb-[200rpx]">
        <u-navbar :border-bottom="false" :background="{ background: '#FFFFFF' }" title="私域互动管家" title-bold />

        <template v-if="loading">
            <view class="px-[24rpx] pt-[24rpx]">
                <view class="flex items-center gap-[12rpx] mb-[20rpx]">
                    <view class="w-[28rpx] h-[28rpx] rounded-full bg-[#F3F4F6] animate-pulse"></view>
                    <view class="h-[24rpx] w-[160rpx] bg-[#F3F4F6] rounded-full animate-pulse"></view>
                </view>
                <view v-for="i in 4" :key="i" class="bg-white rounded-[32rpx] p-[32rpx] mb-[24rpx] animate-pulse">
                    <view class="flex items-center gap-[16rpx] mb-[24rpx]">
                        <view class="w-[56rpx] h-[56rpx] rounded-full bg-[#F3F4F6]"></view>
                        <view class="h-[32rpx] w-[200rpx] bg-[#F3F4F6] rounded-full"></view>
                    </view>
                    <view class="h-[160rpx] bg-[#F3F4F6] rounded-[20rpx]"></view>
                </view>
            </view>
        </template>

        <template v-else>
            <view class="flex items-center gap-[12rpx] px-[32rpx] pt-[12rpx] pb-[24rpx] bg-white">
                <view class="w-[44rpx] h-[44rpx] bg-[#F2F5FF] rounded-full flex items-center justify-center">
                    <u-icon name="account-fill" color="#3B71E8" size="22"></u-icon>
                </view>
                <text class="text-[24rpx] text-[#666666]">当前配置IP：</text>
                <text class="text-[26rpx] font-bold text-[#1A1A1A]">{{ detail.persona_name }}</text>
            </view>
            <view class="px-[24rpx] pt-[24rpx]">
                <view
                    class="bg-white rounded-[32rpx] mb-[24rpx] overflow-hidden shadow-[0_4rpx_20rpx_rgba(0,0,0,0.04)]">
                    <view class="flex items-center gap-[16rpx] px-[32rpx] py-[32rpx]" @click="togglePanel('friend')">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full bg-[#E6F8F3] flex items-center justify-center flex-shrink-0">
                            <u-icon name="chat-fill" color="#00C08E" size="28"></u-icon>
                        </view>
                        <text class="flex-1 text-[30rpx] font-extrabold text-[#1A1A1A]">加好友设置</text>
                        <u-icon
                            :name="openPanels.friend ? 'arrow-down' : 'arrow-right'"
                            color="#C7C7CC"
                            size="20"></u-icon>
                    </view>

                    <view v-if="openPanels.friend" class="border-t border-[#F0F2F5] px-[32rpx] py-[32rpx] bg-[#FAFBFF]">
                        <view class="flex items-center justify-between mb-[28rpx]">
                            <view>
                                <text class="text-[28rpx] font-bold text-[#1A1A1A] block">客资线索库</text>
                                <text class="text-[22rpx] text-[#B4B4B4] mt-[6rpx] block"
                                    >自动提取线索发起好友申请</text
                                >
                            </view>
                            <view class="px-[20rpx] py-[8rpx] bg-[#E6F0FF] rounded-[32rpx]">
                                <text class="text-[22rpx] font-bold text-primary"
                                    >待添加 {{ formData.clue_count }}人</text
                                >
                            </view>
                        </view>
                        <text class="text-[28rpx] font-bold text-[#1A1A1A] block mb-[16rpx]">好友验证申请话术</text>
                        <view
                            class="bg-[#00000005] rounded-[20rpx] px-[28rpx] py-[20rpx] border border-solid border-[#E8E8E8]">
                            <textarea
                                v-model="formData.add_friend_script"
                                class="w-full text-[#333] leading-[1.8] min-h-[120rpx] text-[28rpx]"
                                placeholder="请输入好友验证申请话术..."
                                placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                :maxlength="200"
                                :auto-height="false"
                                :show-confirm-bar="false" />
                        </view>
                    </view>
                </view>

                <view
                    class="bg-white rounded-[32rpx] mb-[24rpx] overflow-hidden shadow-[0_4rpx_20rpx_rgba(0,0,0,0.04)]">
                    <view class="flex items-center gap-[16rpx] px-[32rpx] py-[32rpx]" @click="togglePanel('group')">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center flex-shrink-0"
                            style="background: linear-gradient(90deg, #43e97b 0%, #38f9d7 100%)">
                            <u-icon name="plus-people-fill" color="#ffffff" size="28"></u-icon>
                        </view>
                        <text class="flex-1 text-[30rpx] font-extrabold text-[#1A1A1A]">自动加群设置</text>
                        <u-icon
                            :name="openPanels.group ? 'arrow-down' : 'arrow-right'"
                            color="#C7C7CC"
                            size="20"></u-icon>
                    </view>

                    <view v-if="openPanels.group" class="border-t border-[#F0F2F5] px-[32rpx] py-[32rpx] bg-[#FAFBFF]">
                        <view class="flex items-center justify-between mb-[8rpx]">
                            <view class="flex items-center gap-[12rpx]">
                                <view
                                    class="w-[40rpx] h-[40rpx] rounded-full bg-[#FFF0E6] flex items-center justify-center flex-shrink-0">
                                    <u-icon name="account" color="#FF8C00" size="22"></u-icon>
                                </view>
                                <text class="text-[28rpx] font-bold text-[#1A1A1A]">指定拉入的销售微信 (真人)</text>
                            </view>
                            <text class="text-[22rpx] text-[#B4B4B4]">{{ formData.sales_wechat.length }} / 5</text>
                        </view>
                        <text class="text-[22rpx] text-[#B4B4B4] block mb-[16rpx]"
                            >机器人建群后，会自动将其拉入群聊中作为主理人。</text
                        >
                        <view
                            class="bg-[#00000005] rounded-[20rpx] px-[28rpx] h-[80rpx] flex items-center border border-solid border-[#E8E8E8]">
                            <u-input
                                v-model="groupSalesInput"
                                placeholder="请输入微信号并按回车添加"
                                placeholder-style="color:#B4B4B4;font-size:26rpx;"
                                :border="false"
                                confirm-type="done"
                                @confirm="handleAddGroupSales" />
                        </view>
                        <view class="flex flex-wrap gap-[12rpx] mt-[16rpx]" v-if="formData.sales_wechat.length > 0">
                            <view
                                v-for="(item, index) in formData.sales_wechat"
                                :key="index"
                                class="flex items-center gap-[10rpx] px-[20rpx] py-[10rpx] bg-[#EEF5FF] border border-solid border-[#D0E6FF] rounded-[20rpx]">
                                <text class="text-[26rpx] text-primary">{{ item }}</text>
                                <view
                                    class="w-[32rpx] h-[32rpx] rounded-full bg-[#0065FB1A] flex items-center justify-center flex-shrink-0"
                                    @click="handleRemoveGroupSales(index)">
                                    <u-icon name="close" color="#0065FB" size="16"></u-icon>
                                </view>
                            </view>
                        </view>
                        <view
                            class="flex items-start gap-[12rpx] bg-[#FFF8F0] border border-solid border-[#FFE0B2] rounded-[20rpx] px-[24rpx] py-[20rpx] mt-[16rpx]">
                            <u-icon
                                name="info-circle"
                                color="#FF8C00"
                                size="28"
                                class="flex-shrink-0 mt-[2rpx]"></u-icon>
                            <text class="text-[22rpx] text-[#CC5500] leading-relaxed flex-1"
                                >强烈建议输入【微信号】或在机器人端统一设置好【备注名】，避免因昵称包含特殊符号导致拉人失败。</text
                            >
                        </view>

                        <view class="h-[1rpx] bg-[#F3F4F6] my-[32rpx]"></view>

                        <view class="flex items-center justify-between mb-[16rpx]">
                            <text class="text-[28rpx] font-bold text-[#1A1A1A]">群名称模板</text>
                            <text class="text-[22rpx] text-[#B4B4B4]"
                                >{{ formData.group_name_template.length }} / 32</text
                            >
                        </view>
                        <view
                            class="bg-[#00000005] rounded-[20rpx] px-[28rpx] py-[20rpx] border border-solid border-[#E8E8E8]">
                            <u-input
                                v-model="formData.group_name_template"
                                placeholder="请输入群名称模板"
                                placeholder-style="color:#B4B4B4;font-size:26rpx;"
                                :border="false"
                                maxlength="32"
                                type="textarea"
                                :auto-height="true" />
                        </view>
                        <view class="flex flex-wrap gap-[12rpx] mt-[16rpx]">
                            <view
                                class="flex items-center gap-[6rpx] bg-[#E6F0FF] border border-solid border-[#0065fb]/20 rounded-[20rpx] px-[24rpx] py-[10rpx]"
                                @click="insertGroupNameTemplate('{客户名}')">
                                <u-icon name="plus" color="#0065fb" size="24"></u-icon>
                                <text class="text-[26rpx] text-primary font-bold">插入客户名</text>
                            </view>
                            <view
                                class="flex items-center gap-[6rpx] bg-[#E6F0FF] border border-solid border-[#0065fb]/20 rounded-[20rpx] px-[24rpx] py-[10rpx]"
                                @click="insertGroupNameTemplate('{销售名}')">
                                <u-icon name="plus" color="#0065fb" size="24"></u-icon>
                                <text class="text-[26rpx] text-primary font-bold">插入销售名</text>
                            </view>
                            <view
                                class="flex items-center gap-[6rpx] bg-[#E6F0FF] border border-solid border-[#0065fb]/20 rounded-[20rpx] px-[24rpx] py-[10rpx]"
                                @click="insertGroupNameTemplate('{日期}')">
                                <u-icon name="plus" color="#0065fb" size="24"></u-icon>
                                <text class="text-[26rpx] text-primary font-bold">插入日期</text>
                            </view>
                        </view>

                        <view class="h-[1rpx] bg-[#F3F4F6] my-[32rpx]"></view>

                        <view class="flex items-center justify-between mb-[24rpx]">
                            <text class="text-[28rpx] font-bold text-[#1A1A1A]">建群后自动发送欢迎语</text>
                            <u-switch v-model="formData.is_greeting" :active-value="1" :inactive-value="0" :size="40" />
                        </view>
                        <view v-if="formData.is_greeting == 1">
                            <view
                                class="bg-[#00000005] rounded-[20rpx] px-[28rpx] py-[20rpx] border border-solid border-[#E8E8E8]">
                                <textarea
                                    v-model="formData.greeting_text"
                                    class="w-full text-[#333] leading-[1.8] min-h-[140rpx] text-[28rpx]"
                                    placeholder="请输入建群欢迎语..."
                                    placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                    :maxlength="500"
                                    :auto-height="false"
                                    :show-confirm-bar="false" />
                            </view>
                            <view class="flex flex-wrap gap-[12rpx] mt-[16rpx]">
                                <view
                                    class="flex items-center gap-[6rpx] bg-[#E6F0FF] border border-solid border-[#0065fb]/20 rounded-[20rpx] px-[24rpx] py-[10rpx]"
                                    @click="insertWelcomeContent('{客户名}')">
                                    <u-icon name="plus" color="#0065fb" size="24"></u-icon>
                                    <text class="text-[26rpx] text-primary font-bold">插入客户名</text>
                                </view>
                                <view
                                    class="flex items-center gap-[6rpx] bg-[#E6F0FF] border border-solid border-[#0065fb]/20 rounded-[20rpx] px-[24rpx] py-[10rpx]"
                                    @click="insertWelcomeContent('@{客户}')">
                                    <u-icon name="plus" color="#0065fb" size="24"></u-icon>
                                    <text class="text-[26rpx] text-primary font-bold">@客户</text>
                                </view>
                            </view>
                        </view>

                        <view class="h-[1rpx] bg-[#F0F2F5] my-[32rpx]"></view>

                        <view class="flex items-center justify-between">
                            <view class="flex-1 pr-[24rpx]">
                                <text class="text-[28rpx] font-semibold text-[#1A1A1A] block">携带历史聊天记录</text>
                                <text class="text-[22rpx] text-[#9CA3AF] mt-[8rpx] block leading-relaxed"
                                    >建群后，自动将拉群前的单聊历史记录同步转发至新群聊中</text
                                >
                            </view>
                            <u-switch
                                v-model="formData.is_share_chats"
                                :active-value="1"
                                :inactive-value="0"
                                :size="40" />
                        </view>
                    </view>
                </view>

                <view
                    class="bg-white rounded-[32rpx] mb-[24rpx] overflow-hidden shadow-[0_4rpx_20rpx_rgba(0,0,0,0.04)]">
                    <view class="flex items-center gap-[16rpx] px-[32rpx] py-[32rpx]" @click="togglePanel('limit')">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center flex-shrink-0"
                            style="background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%)">
                            <u-icon name="setting-fill" color="#ffffff" size="28"></u-icon>
                        </view>
                        <text class="flex-1 text-[30rpx] font-extrabold text-[#1A1A1A]">防封控与频率限制</text>
                        <u-icon
                            :name="openPanels.limit ? 'arrow-down' : 'arrow-right'"
                            color="#C7C7CC"
                            size="20"></u-icon>
                    </view>

                    <view v-if="openPanels.limit" class="border-t border-[#F0F2F5] px-[32rpx] py-[32rpx] bg-[#FAFBFF]">
                        <view class="bg-[#E6F0FF]/60 rounded-[16rpx] px-[24rpx] py-[20rpx] mb-[32rpx]">
                            <text class="text-[22rpx] text-primary leading-relaxed"
                                >已开启「拟人随机停顿」。每次互动后，系统将随机停留
                                30秒~2分钟，模拟真人浏览行为，降低风控风险。</text
                            >
                        </view>
                        <view class="flex items-center justify-between mb-[16rpx]">
                            <text class="text-[28rpx] font-bold text-[#1A1A1A]">每天互动人数（仅互动当天）</text>
                            <text class="text-[32rpx] font-extrabold text-primary">{{ formData.number }}人</text>
                        </view>
                        <view class="mb-[8rpx]">
                            <u-slider
                                v-model="formData.number"
                                min="1"
                                max="30"
                                inactive-color="#E5E7EB"
                                block-width="36" />
                        </view>
                        <view class="flex items-center justify-between">
                            <text class="text-[22rpx] text-[#B4B4B4]">保守（防封）</text>
                            <text class="text-[22rpx] text-[#B4B4B4]">激进（易封）</text>
                        </view>
                    </view>
                </view>

                <view
                    class="flex items-center justify-between bg-white rounded-[32rpx] mb-[24rpx] px-[32rpx] py-[32rpx] shadow-[0_4rpx_20rpx_rgba(0,0,0,0.04)]">
                    <view class="flex items-center gap-[16rpx]">
                        <view
                            class="w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center flex-shrink-0"
                            style="background: linear-gradient(90deg, #f7971e 0%, #ffd200 100%)">
                            <u-icon name="photo" color="#ffffff" size="28"></u-icon>
                        </view>
                        <text class="text-[30rpx] font-extrabold text-[#1A1A1A]">朋友圈发布设置</text>
                    </view>
                    <navigator
                        :url="`/ai_modules/person/pages/setting_circle_publish/setting_circle_publish?person_id=${personId}`"
                        hover-class="none">
                        <text class="text-[28rpx] text-primary font-bold">内容设置</text>
                    </navigator>
                </view>

                <view class="h-[48rpx]"></view>
            </view>

            <view class="fixed bottom-0 left-0 right-0 bg-white px-5 pt-3 pb-2 z-50">
                <u-button
                    type="primary"
                    shape="circle"
                    :ripple="true"
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
    is_auto_group: 0 | 1;
    sales_wechat: string[];
    group_name_template: string;
    is_greeting: 0 | 1;
    greeting_text: string;
    is_share_chats: 0 | 1;
}

const { on } = useEventBusManager();

const loading = ref<boolean>(true);
const saving = ref<boolean>(false);
const personId = ref<string>("");
const detail = ref<PersonDetail>({ id: "", industryType: "" });

const formData = reactive<FormData>({
    clue_count: 0,
    add_friend_script: "",
    is_like: 1,
    is_comment: 1,
    comment_method: 1,
    comment_speech: [],
    number: 15,
    comment_robot_prompt: "",
    is_auto_group: 0,
    sales_wechat: [],
    group_name_template: "{客户名}的专属VIP服务群",
    is_greeting: 1,
    greeting_text: "哈喽{客户名}，欢迎！我是您的专属销售顾问，以后有任何问题都可以直接在这个群里找我哦~",
    is_share_chats: 0,
});

const ALL_PANEL_KEYS = ["friend", "group", "limit"];

const openPanels = ref<Record<string, boolean>>(Object.fromEntries(ALL_PANEL_KEYS.map((k) => [k, true])));

const togglePanel = (key: string): void => {
    openPanels.value = { ...openPanels.value, [key]: !openPanels.value[key] };
};

const groupSalesInput = ref<string>("");

const handleAddGroupSales = (): void => {
    const val = groupSalesInput.value.trim();
    if (!val) return;
    if (formData.sales_wechat.length >= 5) {
        uni.showToast({ title: "最多添加5个销售微信", icon: "none" });
        return;
    }
    if (formData.sales_wechat.includes(val)) {
        uni.showToast({ title: "该微信号已添加", icon: "none" });
        return;
    }
    formData.sales_wechat.push(val);
    groupSalesInput.value = "";
};

const handleRemoveGroupSales = (index: number): void => {
    formData.sales_wechat.splice(index, 1);
};

const insertGroupNameTemplate = (variable: string): void => {
    if (formData.group_name_template.length + variable.length > 32) {
        uni.showToast({ title: "群名称模板最多32个字符", icon: "none" });
        return;
    }
    formData.group_name_template += variable;
};

const insertWelcomeContent = (variable: string): void => {
    formData.greeting_text += variable;
};

const openCommentPrompt = (): void => {
    uni.$u.route({
        url: "/ai_modules/person/pages/prompt_set/prompt_set",
        params: {
            type: ListenerTypeEnum.CIRCLE_INTERACT_PROMPT,
            prompt: formData.comment_robot_prompt,
        },
    });
};

const keywordsEditRef = shallowRef<InstanceType<typeof KeywordsEdit>>();
const showKeywordsEdit = ref<boolean>(false);
const keywordsEditIndex = ref<number>(-1);

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

const validateForm = (): string | null => {
    if (!formData.add_friend_script.trim()) return "请输入好友验证申请话术";
    if (formData.comment_method == 1 && !formData.comment_robot_prompt.trim()) {
        openCommentPrompt();
        return "请前往评论机器人页面，设置评论机器人提示词";
    }
    if (formData.comment_method == 2 && formData.comment_speech.length === 0) {
        return "请至少添加一条固定话术";
    }
    if (formData.is_auto_group == 1) {
        if (formData.sales_wechat.length === 0) return "请添加至少一个销售微信";
        if (!formData.group_name_template.trim()) return "请输入群名称模板";
        if (formData.is_greeting == 1 && !formData.greeting_text.trim()) return "请输入建群欢迎语内容";
    }
    return null;
};

const handleSaveConfig = async (): Promise<void> => {
    const errMsg = validateForm();
    if (errMsg) {
        uni.showToast({ title: errMsg, icon: "none", duration: 2000 });
        return;
    }
    if (saving.value) return;
    try {
        saving.value = true;
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
