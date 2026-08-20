<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <u-navbar
            title="智能拼图"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: '#ffffff' }" />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <view class="flex items-start">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="flex-1 flex flex-col items-center relative"
                    @click="handleStep(item.step)">
                    <view
                        v-if="item.step < steps.length"
                        class="absolute top-[28rpx] left-1/2 w-full h-[4rpx] rounded-full z-0 transition-all duration-500"
                        :class="step > item.step ? 'bg-primary' : 'bg-[#F0F2F5]'" />
                    <view
                        class="relative z-10 w-[56rpx] h-[56rpx] rounded-full flex items-center justify-center transition-all duration-300"
                        :class="{
                            'bg-primary shadow-[0_0_0_8rpx_rgba(28,111,235,0.12)]': step === item.step,
                            'bg-primary': step > item.step,
                            'bg-[#F0F2F5]': step < item.step,
                        }">
                        <u-icon v-if="step > item.step" name="checkmark" color="#fff" size="20" />
                        <text
                            v-else
                            class="text-[22rpx] font-bold"
                            :class="step === item.step ? 'text-white' : 'text-[#9CA3AF]'">
                            {{ item.step }}
                        </text>
                    </view>
                    <text
                        class="mt-[8rpx] text-[20rpx] transition-all duration-300"
                        :class="{
                            'text-primary font-bold': step === item.step,
                            'text-[#4B5563] font-semibold': step > item.step,
                            'text-[#9CA3AF] font-medium': step < item.step,
                        }">
                        {{ item.title }}
                    </text>
                </view>
            </view>
        </view>

        <view class="grow min-h-0">
            <view class="h-full flex flex-col" v-show="step === 1">
                <view class="px-4 pt-4 flex-shrink-0 space-y-[12rpx]">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">上传图片</text>
                        </view>
                        <text class="text-[22rpx] text-[#9CA3AF]">
                            已上传
                            <text class="text-primary font-bold">{{ formData.materialList.length }}</text>
                            张
                        </text>
                    </view>
                    <view class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                        <u-icon name="info-circle" color="#0065fb" size="20" />
                        <text class="text-[22rpx] text-primary font-medium">至少需要上传 2 张图片，系统将自动拼图</text>
                    </view>
                </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="grid grid-cols-3 gap-[16rpx] p-4">
                            <view
                                v-for="(item, index) in formData.materialList"
                                :key="index"
                                class="relative rounded-[24rpx] overflow-hidden aspect-[3/4]">
                                <image
                                    :src="item"
                                    class="w-full h-full"
                                    mode="aspectFill"
                                    @click="previewMaterial(item)" />
                                <view
                                    class="absolute bottom-0 left-0 right-0 h-[100rpx]"
                                    style="background: linear-gradient(to top, rgba(0, 0, 0, 0.45), transparent)" />
                                <view class="absolute bottom-[12rpx] left-[12rpx]">
                                    <view
                                        class="px-[16rpx] py-[6rpx] text-white text-[22rpx] rounded-full border border-solid border-[#ffffff]/40 bg-[#000000]/30"
                                        @click="handleReplaceMaterial(index)">
                                        替换
                                    </view>
                                </view>
                                <view
                                    class="absolute top-[12rpx] right-[12rpx] w-[40rpx] h-[40rpx] rounded-full bg-[#000000]/40 flex items-center justify-center"
                                    @click="handleDeleteMaterial(index)">
                                    <u-icon name="close" color="#ffffff" size="14" />
                                </view>
                            </view>
                            <view
                                class="bg-white rounded-[24rpx] flex flex-col items-center justify-center aspect-[3/4] border border-dashed border-[#0065fb]/30"
                                @click="chooseUploadType">
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                    <u-icon name="plus" size="24" color="#0065fb" />
                                </view>
                                <text class="text-[22rpx] text-[#9CA3AF] mt-[16rpx]">添加图片</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view class="h-full flex flex-col" v-show="step === 2">
                <view class="px-4 pt-4 pb-[16rpx] flex-shrink-0">
                    <view class="flex items-center h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">填写主题</text>
                        </view>
                    </view>
                    <view class="flex items-center gap-[16rpx]">
                        <view
                            class="flex-1 flex items-center justify-center gap-[10rpx] h-[96rpx] rounded-[24rpx] bg-white border border-solid border-[#E5E9F0] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.04)]"
                            @click="openCopywriterEditor()">
                            <u-icon name="edit-pen" color="#4B5563" size="22" />
                            <text class="text-[28rpx] font-bold text-[#334155]">手动输入</text>
                        </view>
                        <navigator
                            url="/ai_modules/drawing/pages/puzzle_ai_copywriter/puzzle_ai_copywriter"
                            hover-class="none"
                            class="flex-1 h-[96rpx] flex items-center justify-center gap-[10rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_20rpx_rgba(0,101,251,0.25)]"
                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                            @click="editCopywriterIndex = -1">
                            <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]" />
                            <text class="text-[28rpx] font-bold text-white">AI 生成</text>
                        </navigator>
                    </view>
                </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4 flex flex-col gap-[16rpx] pb-4">
                            <view
                                v-for="(item, index) in formData.copywriterList"
                                :key="index"
                                class="bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                                @click="handleSelectCopywriter(index)">
                                <view class="flex">
                                    <view class="w-[6rpx] flex-shrink-0 bg-primary rounded-l-[24rpx]" />
                                    <view class="flex-1 px-[24rpx] pt-[20rpx] pb-[18rpx]">
                                        <view class="flex items-center justify-between mb-[16rpx]">
                                            <view class="flex items-center gap-[10rpx]">
                                                <view
                                                    class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                                    <text class="text-[22rpx] font-bold text-primary">{{
                                                        index + 1
                                                    }}</text>
                                                </view>
                                                <text class="text-xs text-[#9CA3AF]">拼图主题</text>
                                            </view>
                                            <view
                                                class="w-[44rpx] h-[44rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                                                @click.stop="handleDeleteCopywriter(index)">
                                                <u-icon name="close" color="#9CA3AF" size="16" />
                                            </view>
                                        </view>
                                        <view
                                            v-for="(val, valIndex) in item"
                                            :key="valIndex"
                                            class="flex items-center gap-[16rpx] py-[12rpx]"
                                            :class="
                                                valIndex < item.length - 1
                                                    ? 'border-[0] border-b border-solid border-[#F0F2F5]'
                                                    : ''
                                            ">
                                            <view class="flex-shrink-0 px-[12rpx] py-[4rpx] rounded-full bg-[#F0F2F5]">
                                                <text class="text-[22rpx] text-[#64748B] font-semibold">
                                                    {{ valIndex === 0 ? "主标题" : "副标题" }}
                                                </text>
                                            </view>
                                            <text class="flex-1 text-[#334155] line-clamp-1">{{ val }}</text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view
                                v-if="formData.copywriterList.length === 0"
                                class="flex flex-col items-center justify-center py-[80rpx] bg-white rounded-[28rpx] border border-dashed border-[#E5E9F0]">
                                <view
                                    class="w-[80rpx] h-[80rpx] rounded-full bg-[#F0F2F5] flex items-center justify-center mb-[16rpx]">
                                    <u-icon name="file-text" color="#9CA3AF" size="28" />
                                </view>
                                <text class="text-[#9CA3AF]">暂无主题，请手动输入或 AI 生成</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view class="h-full" v-show="step === 3">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pt-4 pb-[32rpx] flex flex-col gap-[16rpx]">
                        <view class="flex items-center h-[80rpx]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[32rpx] font-extrabold text-[#0D1117]">生成设置</text>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117]">作品名称</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                                    <u-input
                                        v-model="formData.name"
                                        maxlength="50"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="请输入作品名称"
                                        clearable />
                                </view>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117]">内容汇总</text>
                            </view>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="handleStep(1)">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">参考素材</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.materialList.length }}</text>
                                    <text class="text-[#9CA3AF]">张</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                            <view class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx]" @click="handleStep(2)">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">拼图主题</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.copywriterList.length }}</text>
                                    <text class="text-[#9CA3AF]">个</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] flex items-center justify-between shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view>
                                <text class="text-[28rpx] font-bold text-[#0D1117] block mb-[6rpx]">生成拼图数量</text>
                                <text class="text-[22rpx] text-[#9CA3AF]">数量须为 4 的倍数</text>
                            </view>
                            <view class="flex items-center gap-[16rpx]">
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                    @click="handleResultCount('minus')">
                                    <text class="text-[32rpx] text-primary font-bold leading-none">−</text>
                                </view>
                                <view
                                    class="w-[80rpx] h-[56rpx] bg-[#EBF2FF] rounded-[14rpx] flex items-center justify-center">
                                    <u-input
                                        v-model="formData.result_count"
                                        type="digit"
                                        placeholder=""
                                        :custom-style="{
                                            color: '#0065fb',
                                            fontWeight: '800',
                                            fontSize: '30rpx',
                                        }"
                                        input-align="center" />
                                </view>
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                    @click="handleResultCount('add')">
                                    <text class="text-[32rpx] text-primary font-bold leading-none">＋</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]">
            <template v-if="step !== steps.length">
                <view
                    v-if="step === 1"
                    class="w-[100rpx] h-[96rpx] rounded-[20rpx] flex flex-col items-center justify-center transition-all duration-300"
                    :class="formData.materialList.length > 0 ? 'bg-[#EBF2FF]' : 'bg-[#F0F2F5]'">
                    <text
                        class="text-[32rpx] font-extrabold leading-none"
                        :class="formData.materialList.length > 0 ? 'text-primary' : 'text-[#C0C4CC]'">
                        {{ formData.materialList.length }}
                    </text>
                    <text
                        class="text-[20rpx] mt-[4rpx]"
                        :class="formData.materialList.length > 0 ? 'text-[#0065fb]/70' : 'text-[#C0C4CC]'">
                        已选
                    </text>
                </view>
                <view
                    v-else
                    class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-white"
                    @click="handleStep(step, 'prev')">
                    <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
                </view>
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] transition-all duration-300"
                    :class="canNext ? 'shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : ''"
                    :style="
                        canNext
                            ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                            : 'background: #C0C4CC'
                    "
                    @click="handleStep(step, 'next')">
                    <text class="text-[30rpx] font-bold text-white">下一步</text>
                </view>
            </template>
            <template v-else>
                <view
                    class="flex-1 h-[100rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleCreateImage">
                    <text class="text-[32rpx] font-extrabold text-white tracking-wide">
                        生成拼图（{{ formData.result_count }}个）
                    </text>
                    <view class="flex items-center gap-[4rpx] bg-[#ffffff]/20 rounded-full px-[16rpx] py-[6rpx]">
                        <text class="text-[22rpx] text-white font-medium">消耗 {{ tokenCost }} 算力</text>
                    </view>
                </view>
            </template>
        </view>
    </view>

    <u-popup v-model="showUploadTip" mode="center" border-radius="32" width="90%">
        <view class="bg-white rounded-[32rpx] overflow-hidden">
            <view
                class="flex items-center justify-center gap-[10rpx] px-[40rpx] h-[96rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                style="background: linear-gradient(135deg, #ebf2ff 0%, #f0f9ff 100%)">
                <u-icon name="info-circle-fill" color="#0065fb" size="28" />
                <text class="text-[30rpx] font-extrabold text-[#0D1117]">图片上传须知</text>
            </view>

            <view class="px-[40rpx] py-[32rpx] flex flex-col gap-[20rpx]">
                <view class="bg-[#F0F6FF] rounded-[20rpx] px-[24rpx] py-[20rpx] flex items-start gap-[14rpx]">
                    <view
                        class="w-[32rpx] h-[32rpx] rounded-full bg-primary flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                        <text class="text-white text-[18rpx] font-extrabold">1</text>
                    </view>
                    <text class="text-xs text-[#374151] leading-relaxed flex-1">
                        至少需要传
                        <text class="text-primary font-semibold">2 张</text>图片，根据上传的图片数量随机拼图和包装
                    </text>
                </view>

                <view class="bg-[#FEF2F2] rounded-[20rpx] px-[24rpx] py-[20rpx] flex items-start gap-[14rpx]">
                    <view
                        class="w-[32rpx] h-[32rpx] rounded-full bg-[#EF4444] flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                        <u-icon name="warning" color="#fff" size="16" />
                    </view>
                    <text class="text-xs text-[#EF4444] leading-relaxed flex-1 font-semibold">
                        不符合条件的素材会被自动删除
                    </text>
                </view>
            </view>

            <view class="flex items-center gap-[16rpx] px-[40rpx] pb-[40rpx]">
                <view
                    class="flex-1 h-[96rpx] flex items-center justify-center rounded-[24rpx] bg-[#F0F2F5] border border-solid border-[#E5E9F0]"
                    @click="cancelUploadTip">
                    <text class="text-[28rpx] font-semibold text-[#4B5563]">取消</text>
                </view>
                <view
                    class="flex-1 h-[96rpx] flex items-center justify-center rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_24rpx_rgba(0,101,251,0.30)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="confirmUploadTip">
                    <text class="text-[28rpx] font-extrabold text-white">去上传</text>
                </view>
            </view>
        </view>
    </u-popup>

    <u-popup
        v-model="showCreateSuccess"
        mode="center"
        border-radius="20"
        width="85%"
        :custom-style="{ backgroundColor: 'transparent' }"
        :mask-close-able="false">
        <view class="w-full bg-white rounded-[28rpx] px-[48rpx] py-[60rpx]">
            <view class="flex flex-col items-center mb-[32rpx]">
                <view class="w-[96rpx] h-[96rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center mb-[20rpx]">
                    <u-icon name="checkmark-circle-fill" color="#0065fb" size="48" />
                </view>
                <text class="text-[36rpx] font-extrabold text-[#0D1117]">拼图生成中</text>
            </view>
            <view class="bg-[#F7F9FC] rounded-[16rpx] px-[24rpx] py-[20rpx] mb-[40rpx]">
                <text class="text-[#4B5563] leading-relaxed">
                    拼图生成成功后，您可以自定义设置图组数量去发布图文任务
                </text>
            </view>
            <view
                class="h-[96rpx] rounded-[24rpx] flex items-center justify-center relative overflow-hidden shadow-[0_8rpx_20rpx_rgba(0,101,251,0.25)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="toPuzzleRecord">
                <text class="text-[28rpx] font-bold text-white">查看拼图创作</text>
            </view>
        </view>
    </u-popup>

    <upload-category-panel
        v-model="showUploadCategoryPanel"
        :show-categories="['album', 'library', 'creation']"
        @select="handleSelectCategory" />
    <choose-material
        v-model="showMaterial"
        type="image"
        :limit="replaceMaterialIndex === -1 ? limit - formData.materialList.length : 1"
        @select="handleSelectMaterial" />
    <choose-history
        v-model="showHistory"
        type="image"
        :limit="replaceMaterialIndex === -1 ? limit - formData.materialList.length : 1"
        @select="handleSelectHistory" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <recharge-popup ref="rechargePopupRef" />
</template>

<script setup lang="ts">
import { createPuzzleTask } from "@/api/drawing";
import { useUserStore } from "@/stores/user";
import useUpload from "@/hooks/useUpload";
import { TokensSceneEnum } from "@/enums/appEnums";
import { ListenerTypeEnum } from "@/ai_modules/drawing/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";

// ─── 常量 ─────────────────────────────────────────────────────────
const RESULT_COUNT_MIN = 4;
const RESULT_COUNT_MAX = 100;
const RESULT_COUNT_STEP = 4;
const MATERIAL_LIMIT = 100;
const IMAGE_ACCEPT = ["jpg", "png", "jpeg"];
const IMAGE_SIZE = 5;
const IMAGE_RESOLUTION: [number, number] = [2000, 2000];

// ─── 步骤配置 ──────────────────────────────────────────────────────
const steps = [
    { step: 1, title: "上传图片" },
    { step: 2, title: "填写主题" },
    { step: 3, title: "生成设置" },
];

// ─── 表单数据 ──────────────────────────────────────────────────────
const formData = reactive({
    copywriterList: [] as any[],
    materialList: [] as string[],
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "拼图",
    result_count: RESULT_COUNT_MIN,
});

// ─── Refs ──────────────────────────────────────────────────────────
const rechargePopupRef = shallowRef<any>();
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const { on } = useEventBusManager();

// ─── 算力消耗 ──────────────────────────────────────────────────────
const tokenCost = computed(() => userStore.getTokenByScene(TokensSceneEnum.PUZZLE_CREATE)?.score);

// ─── Hook：步骤导航 ────────────────────────────────────────────────
const { step, canNext, handleStep } = useStepNav(formData);

// ─── Hook：素材管理 ────────────────────────────────────────────────
const {
    limit,
    replaceMaterialIndex,
    showUploadCategoryPanel,
    showMaterial,
    showHistory,
    showUploadTip,
    showUploadProgress,
    uploadMaterialList,
    chooseUploadType,
    cancelUploadTip,
    confirmUploadTip,
    handleSelectCategory,
    handleSelectMaterial,
    handleSelectHistory,
    handleReplaceMaterial,
    handleDeleteMaterial,
    previewMaterial,
} = useMaterialManager(formData);

// ─── Hook：文案管理 ────────────────────────────────────────────────
const { editCopywriterIndex, openCopywriterEditor, handleSelectCopywriter, handleDeleteCopywriter } =
    useCopywriterManager(formData);

// ─── 生成数量控制 ──────────────────────────────────────────────────
const handleResultCount = (type: "minus" | "add") => {
    const count = formData.result_count;
    if (type === "minus") {
        if (count <= RESULT_COUNT_MIN) {
            uni.$u.toast(`最少生成${RESULT_COUNT_MIN}个拼图哦`);
            return;
        }
        formData.result_count = Math.floor((count - 1) / RESULT_COUNT_STEP) * RESULT_COUNT_STEP;
    } else {
        if (count >= RESULT_COUNT_MAX) {
            uni.$u.toast(`最多生成${RESULT_COUNT_MAX}个拼图哦`);
            formData.result_count = RESULT_COUNT_MAX;
            return;
        }
        formData.result_count = Math.ceil((count + 1) / RESULT_COUNT_STEP) * RESULT_COUNT_STEP;
    }
};

// ─── 提交生成 ──────────────────────────────────────────────────────
const showCreateSuccess = ref(false);

const handleCreateImage = async () => {
    if (userTokens.value <= tokenCost.value) {
        rechargePopupRef.value?.open();
        return;
    }
    if (formData.result_count < RESULT_COUNT_MIN) {
        uni.$u.toast(`最少生成${RESULT_COUNT_MIN}个拼图哦`);
        return;
    }
    if (formData.result_count % RESULT_COUNT_STEP !== 0) {
        uni.$u.toast(`生成数量必须是${RESULT_COUNT_STEP}的倍数`);
        return;
    }
    uni.showLoading({ title: "提交中...", mask: true });
    try {
        await createPuzzleTask({
            name: formData.name,
            copywriting: formData.copywriterList.map((item: any) => ({ title: item })),
            material: formData.materialList,
            result_count: formData.result_count,
        });
        showCreateSuccess.value = true;
    } catch (error: any) {
        uni.showToast({ title: error, icon: "none", duration: 3000 });
    } finally {
        uni.hideLoading();
    }
};

const toPuzzleRecord = () => {
    uni.$u.route({ url: "/packages/pages/creation/creation?tab=2", type: "reLaunch" });
};

// ─── EventBus ──────────────────────────────────────────────────────
onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type !== ListenerTypeEnum.PUZZLE_AI_COPYWRITER && type !== ListenerTypeEnum.PUZZLE_COPYWRITER) return;
        if (!data?.length) return;

        if (editCopywriterIndex.value !== -1) {
            formData.copywriterList[editCopywriterIndex.value] = data[0];
        } else {
            formData.copywriterList.push(...data);
        }
    });
});

// ════════════════════════════════════════════════════════════════
// Hook：useStepNav —— 步骤导航
// ════════════════════════════════════════════════════════════════
function useStepNav(formData: any) {
    const step = ref(1);

    const STEP_VALIDATE_MSG: Record<number, string> = {
        1: "请上传图片",
        2: "请填写主题",
    };

    const canStepProceed = (s: number): boolean => {
        if (s === 1) return formData.materialList.length > 1;
        if (s === 2) return formData.copywriterList.length > 0;
        return true;
    };

    const canNext = computed(() => canStepProceed(step.value));

    const handleStep = (targetStep: number, type?: "next" | "prev") => {
        if (type === "prev") {
            step.value--;
            return;
        }
        if (type === "next") {
            if (canNext.value) {
                step.value++;
            } else {
                uni.$u.toast(STEP_VALIDATE_MSG[step.value] || "请完成当前步骤");
            }
            return;
        }
        // 点击步骤条跳转
        if (targetStep === step.value) return;
        if (targetStep < step.value) {
            step.value = targetStep;
            return;
        }
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast("请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    };

    return { step, canNext, handleStep };
}

// ════════════════════════════════════════════════════════════════
// Hook：useMaterialManager —— 素材上传 / 选择 / 替换 / 删除
// ════════════════════════════════════════════════════════════════
function useMaterialManager(formData: any) {
    const limit = MATERIAL_LIMIT;
    const replaceMaterialIndex = ref(-1);
    const showUploadCategoryPanel = ref(false);
    const showMaterial = ref(false);
    const showHistory = ref(false);
    const showUploadTip = ref(false);
    const isFirstOpen = ref(true);

    /**
     * 统一的素材写入逻辑（替换 or 追加）
     * 三处来源（upload / material库 / 历史记录）共用
     */
    const applyMaterials = (urls: string[]) => {
        if (replaceMaterialIndex.value !== -1) {
            formData.materialList[replaceMaterialIndex.value] = urls[0];
        } else {
            formData.materialList.push(...urls);
        }
        replaceMaterialIndex.value = -1;
    };

    const { uploadMaterialList, showUploadProgress, uploadAndProcessFiles } = useUpload({
        isTranscode: true,
        imageAccept: IMAGE_ACCEPT,
        imageSize: IMAGE_SIZE,
        imageResolution: IMAGE_RESOLUTION,
        fileAccept: IMAGE_ACCEPT,
        fileSize: IMAGE_SIZE,
        onSuccess: (materials: any[]) => applyMaterials(materials.map((m) => m.url)),
    });

    // 分类面板选择：用对象映射替代 if-else 链
    const CATEGORY_ACTION: Record<string, () => void> = {
        album: () => uploadAndProcessFiles("image"),
        library: () => (showMaterial.value = true),
        creation: () => (showHistory.value = true),
    };
    const handleSelectCategory = (category: string) => CATEGORY_ACTION[category]?.();

    // 首次打开显示须知，之后直接打开面板
    const chooseUploadType = () => {
        if (isFirstOpen.value) {
            showUploadTip.value = true;
            return;
        }
        showUploadCategoryPanel.value = true;
    };

    // 须知弹窗：取消
    const cancelUploadTip = () => {
        showUploadTip.value = false;
        isFirstOpen.value = false;
    };

    // 须知弹窗：确认去上传
    const confirmUploadTip = () => {
        showUploadTip.value = false;
        isFirstOpen.value = false;
        uploadAndProcessFiles("image");
    };

    // 来自素材库的选择
    const handleSelectMaterial = (lists: any[]) => applyMaterials(lists.map((i) => i.url));

    // 来自历史记录的选择
    const handleSelectHistory = (history: any[]) => applyMaterials(history.map((i) => i.url));

    const handleReplaceMaterial = (index: number) => {
        replaceMaterialIndex.value = index;
        showUploadCategoryPanel.value = true;
    };

    const handleDeleteMaterial = (index: number) => {
        formData.materialList.splice(index, 1);
    };

    const previewMaterial = (pic: string) => {
        uni.previewImage({ urls: [pic] });
    };

    return {
        limit,
        replaceMaterialIndex,
        showUploadCategoryPanel,
        showMaterial,
        showHistory,
        showUploadTip,
        showUploadProgress,
        uploadMaterialList,
        chooseUploadType,
        cancelUploadTip,
        confirmUploadTip,
        handleSelectCategory,
        handleSelectMaterial,
        handleSelectHistory,
        handleReplaceMaterial,
        handleDeleteMaterial,
        previewMaterial,
    };
}

// ════════════════════════════════════════════════════════════════
// Hook：useCopywriterManager —— 文案编辑 / 删除
// ════════════════════════════════════════════════════════════════
function useCopywriterManager(formData: any) {
    const editCopywriterIndex = ref(-1);

    const openCopywriterEditor = (data?: any) => {
        uni.$u.route({
            url: "/ai_modules/drawing/pages/puzzle_copywriter/puzzle_copywriter",
            params: { data: data ? JSON.stringify(data) : "" },
        });
    };

    const handleSelectCopywriter = (index: number) => {
        editCopywriterIndex.value = index;
        openCopywriterEditor(formData.copywriterList[index]);
    };

    const handleDeleteCopywriter = (index: number) => {
        formData.copywriterList.splice(index, 1);
    };

    return {
        editCopywriterIndex,
        openCopywriterEditor,
        handleSelectCopywriter,
        handleDeleteCopywriter,
    };
}
</script>
