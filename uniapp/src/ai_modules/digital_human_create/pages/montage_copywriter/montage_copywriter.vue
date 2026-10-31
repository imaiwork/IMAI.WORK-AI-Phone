<template>
    <view class="min-h-screen bg-[#F7F9FC]">
        <u-navbar
            :is-fixed="false"
            :border-bottom="false"
            is-custom-back-icon
            :custom-back="back"
            :background="{ background: 'transparent' }">
            <template #custom-back-icon>
                <view
                    class="flex items-center justify-center h-[56rpx] px-[28rpx] rounded-[16rpx] shadow-[0_4rpx_12rpx_rgba(0,101,251,0.25)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                    <text class="text-[28rpx] font-extrabold text-white tracking-wide whitespace-nowrap">完成</text>
                </view>
            </template>
        </u-navbar>

        <view class="px-[24rpx] pt-[16rpx] flex flex-col gap-[16rpx]">
            <template v-if="!isNewsBody">
                <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                    <view
                        class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">标题</text>
                        </view>
                        <view class="flex items-center gap-[6rpx] h-[48rpx] px-[16rpx] rounded-[12rpx] bg-[#F0F6FF]">
                            <text
                                class="text-xs font-bold"
                                :class="formData.title?.length >= 100 ? 'text-[#F56C6C]' : 'text-primary'">
                                {{ formData.title?.length || 0 }}
                            </text>
                            <text class="text-[22rpx] text-[#9CA3AF]">/100</text>
                        </view>
                    </view>
                    <view class="px-[28rpx] py-[20rpx]">
                        <u-input
                            v-model="formData.title"
                            placeholder="点击此输入标题"
                            height="72"
                            :maxlength="100"
                            placeholder-style="font-size:32rpx;font-weight:600;color:#C0C4CC;"
                            :custom-style="{
                                fontSize: '32rpx',
                                fontWeight: '600',
                                color: '#0D1117',
                            }" />
                    </view>
                </view>

                <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                    <view
                        class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">口播内容</text>
                        </view>
                        <view class="flex items-center gap-[6rpx] h-[48rpx] px-[16rpx] rounded-[12rpx] bg-[#F0F6FF]">
                            <text
                                class="text-xs font-bold"
                                :class="
                                    formData.content?.length >= copywriterLimit ? 'text-[#F56C6C]' : 'text-primary'
                                ">
                                {{ formData.content?.length || 0 }}
                            </text>
                            <text class="text-[22rpx] text-[#9CA3AF]">/{{ copywriterLimit }}</text>
                        </view>
                    </view>
                    <view class="px-[28rpx] py-[24rpx]">
                        <view class="bg-[#F7F9FC] rounded-[20rpx] px-[20rpx] py-[20rpx]">
                            <u-input
                                v-model="formData.content"
                                placeholder="粘贴你的口播文案或者输入内容"
                                type="textarea"
                                height="480"
                                :maxlength="copywriterLimit"
                                placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                :auto-height="false" />
                        </view>
                    </view>
                </view>
            </template>

            <template v-else>
                <view class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06)]">
                    <view
                        class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">新闻内容</text>
                        </view>
                        <view class="flex items-center gap-[6rpx] h-[48rpx] px-[16rpx] rounded-[12rpx] bg-[#F0F6FF]">
                            <text
                                class="text-xs font-bold"
                                :class="formData.title?.length >= copywriterLimit ? 'text-[#F56C6C]' : 'text-primary'">
                                {{ formData.title?.length || 0 }}
                            </text>
                            <text class="text-[22rpx] text-[#9CA3AF]">/{{ copywriterLimit }}</text>
                        </view>
                    </view>
                    <view class="px-[28rpx] py-[24rpx]">
                        <view class="bg-[#F7F9FC] rounded-[20rpx] px-[20rpx] py-[20rpx]">
                            <u-input
                                v-model="formData.title"
                                placeholder="点击此输入标题"
                                type="textarea"
                                :maxlength="1000"
                                height="480"
                                placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                :auto-height="false" />
                        </view>
                    </view>
                </view>
            </template>
        </view>
    </view>
</template>

<script setup lang="ts">
import { ListenerTypeEnum } from "@/enums/digitalHuman";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { COPYWRITER_LIMIT } from "@/ai_modules/digital_human_create/hooks/useCopywriter";
import { validateText } from "@/utils/textValidator";

const { emit } = useEventBusManager();

const formData = reactive({
    title: "",
    content: "",
});

const copywriterLimit = ref(COPYWRITER_LIMIT);
const isNewsBody = ref(false);
// 语言检测硬拦截模式：由入口页通过 sendData 传入，开启后文案不通过不允许确认
const strictCheck = ref(false);

const back = () => {
    const textsToValidate = isNewsBody.value ? [formData.title] : [formData.title, formData.content].filter(Boolean)
    const mojibakeTexts: string[] = []
    const noSpaceTexts: string[] = []

    for (const text of textsToValidate) {
        const v = validateText(text.trim())
        if (v.status === "error" && v.type === "mojibake") {
            mojibakeTexts.push(text.trim())
        } else if (v.status === "warning" && v.type === "latin_no_space") {
            // 空格拼接 warning（英文/俄文/法文等）：轻提示，不阻断
            noSpaceTexts.push(text.trim())
        }
    }

    // warning 级：toast 提示并停留在当前页，让用户自行修改后再点完成
    if (noSpaceTexts.length > 0 && mojibakeTexts.length === 0) {
        uni.showToast({
            title: `检测到 ${noSpaceTexts.length} 处疑似单词间缺空格，请检查`,
            icon: "none",
            duration: 2500,
        })
        return
    }

    // error 级：Modal 阻断
    if (mojibakeTexts.length > 0) {
        const example = mojibakeTexts[0].slice(0, 80) + (mojibakeTexts[0].length > 80 ? "..." : "")
        // 严格模式：语言检测不通过不允许跳过，必须修改后才能完成
        if (strictCheck.value) {
            uni.showModal({
                title: "检测到外文乱码",
                content: `"${example}"\n\n文案语言检测不通过，请修改后再完成`,
                confirmText: "去修复",
                showCancel: false,
            })
            return
        }
        uni.showModal({
            title: "检测到外文乱码",
            content: `"${example}"\n\n请手动修改后再提交，或跳过直接使用`,
            confirmText: "去修复",
            cancelText: "跳过提交",
            success: (res) => {
                if (!res.confirm) {
                    doBack()
                }
                // 去修复 → 只关闭弹框，用户留在当前页手动修改
            },
        })
        return
    }
    doBack()
}

const doBack = () => {
    if (isNewsBody.value) {
        if (formData.title.trim() === "") {
            uni.$u.toast("请输入标题");
            return;
        }
        emit("confirm", {
            type: ListenerTypeEnum.MONTAGE_COPYWRITER,
            data: [formData.title],
        });
    } else {
        if (!formData.title && !formData.content) {
            uni.navigateBack();
            return;
        }
        if (!formData.content) {
            uni.$u.toast("请输入口播内容");
            return;
        } else if (formData.content.trim().length < 3) {
            uni.$u.toast("口播内容不能少于10个字");
            return;
        }
        emit("confirm", {
            type: ListenerTypeEnum.MONTAGE_COPYWRITER,
            data: [
                {
                    ...formData,
                    title: formData.title || formData.content.slice(0, 12),
                },
            ],
        });
    }
    uni.navigateBack();
};

onLoad((options: any) => {
    // if (options.isNewsBody) {
    //     isNewsBody.value = true;
    //     if (options.data) {
    //         formData.title = options.data;
    //     }
    // } else {
    //     if (options.data) {
    //         const data = JSON.parse(decodeURIComponent(options.data));
    //         formData.title = data.title;
    //         formData.content = data.content;
    //     }
    // }
    // if (options.limit) {
    //     copywriterLimit.value = options.limit;
    // }
    const pages = getCurrentPages();
    const currentPage = pages[pages.length - 1] as any;
    const eventChannel = currentPage.getOpenerEventChannel();
    eventChannel.on("sendData", (data: any) => {
        if (data.isNewsBody) {
            isNewsBody.value = true;
            if (data.title) {
                formData.title = data.title;
            }
        } else {
            formData.title = data.title;
            formData.content = data.content;
        }
        if (data.limit) {
            copywriterLimit.value = data.limit;
        }
        if (data.strictCheck) {
            strictCheck.value = true;
        }
    });
});
</script>

<style scoped></style>
