<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title="智能数字人"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: 'transparent' }" />

        <view class="grow min-h-0">
            <scroll-view scroll-y class="h-full">
                <view class="px-4 pt-[16rpx] pb-[120rpx] flex flex-col gap-[16rpx]">
                    <!-- 克隆模型选择 -->
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">克隆模型选择</text>
                                <text class="text-[#EF4444] text-[28rpx] font-bold">*</text>
                            </view>
                            <text class="text-[22rpx] text-[#9CA3AF]">不同模型算力不同</text>
                        </view>

                        <view class="p-[20rpx]">
                            <view
                                class="bg-[#F7F9FC] border border-solid border-[#EEF1F6] rounded-[28rpx] px-[24rpx] py-[24rpx]"
                                @click="showCloneModeDrop = !showCloneModeDrop">
                                <view class="flex items-center gap-[16rpx]">
                                    <view
                                        class="clone-chip flex-shrink-0"
                                        :class="
                                            currCloneMode === CloneModeEnum.PRO
                                                ? 'clone-chip--pro'
                                                : 'clone-chip--fast'
                                        ">
                                        {{ currCloneModeOption.name }}
                                    </view>
                                    <text class="flex-1 min-w-0 text-[26rpx] font-bold text-[#1F2937]">{{
                                        currCloneModeOption.name
                                    }}</text>
                                    <view class="flex-shrink-0 flex flex-col items-end">
                                        <text class="text-[28rpx] font-extrabold text-[#F86E21] leading-none">{{
                                            currCloneModeOption.cost
                                        }}</text>
                                        <text class="text-[20rpx] text-[#9CA3AF] mt-[4rpx]">算力/个</text>
                                    </view>
                                    <u-icon
                                        :name="showCloneModeDrop ? 'arrow-up' : 'arrow-down'"
                                        color="#9CA3AF"
                                        size="20" />
                                </view>
                                <view class="text-[22rpx] text-[#9CA3AF] mt-[12rpx] leading-relaxed">{{
                                    currCloneModeOption.desc
                                }}</view>
                            </view>

                            <view
                                v-if="showCloneModeDrop"
                                class="mt-[16rpx] bg-white border border-solid border-[#EEF1F6] rounded-[28rpx] overflow-hidden shadow-[0_16rpx_48rpx_rgba(0,0,0,0.08)]">
                                <view
                                    v-for="(item, index) in cloneModeOptions"
                                    :key="item.value"
                                    class="px-[24rpx] py-[24rpx]"
                                    :class="index === 0 ? 'border-[0] border-b border-solid border-[#F5F6F8]' : ''"
                                    @click="handleSelectCloneMode(item.value)">
                                    <view class="flex items-center gap-[16rpx]">
                                        <view
                                            class="clone-chip flex-shrink-0"
                                            :class="
                                                item.value === CloneModeEnum.PRO
                                                    ? 'clone-chip--pro'
                                                    : 'clone-chip--fast'
                                            ">
                                            {{ item.name }}
                                        </view>
                                        <text class="flex-1 min-w-0 text-[26rpx] font-bold text-[#1F2937]">{{
                                            item.name
                                        }}</text>
                                        <view class="flex-shrink-0 flex flex-col items-end mr-[8rpx]">
                                            <text class="text-[26rpx] font-bold text-[#F86E21] leading-none">{{
                                                item.cost
                                            }}</text>
                                            <text class="text-[20rpx] text-[#9CA3AF] mt-[4rpx]">算力/个</text>
                                        </view>
                                        <u-icon
                                            v-if="currCloneMode === item.value"
                                            name="checkmark"
                                            color="#2F73F6"
                                            size="20" />
                                        <view v-else class="w-[40rpx] flex-shrink-0" />
                                    </view>
                                    <view class="text-[22rpx] text-[#9CA3AF] mt-[12rpx] leading-relaxed">{{
                                        item.desc
                                    }}</view>
                                </view>
                            </view>
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">上传形象视频</text>
                                <text class="text-[#EF4444] text-[28rpx] font-bold">*</text>
                            </view>
                            <view
                                v-if="anchorData.url"
                                class="flex items-center gap-[6rpx] h-[56rpx] px-[20rpx] rounded-[14rpx] bg-[#F0F2F5]"
                                @click="handleUploadAnchorVideo">
                                <u-icon name="reload" color="#4B5563" size="18" />
                                <text class="text-xs font-semibold text-[#4B5563]">更换视频</text>
                            </view>
                        </view>

                        <view class="p-[20rpx]">
                            <view
                                v-if="!anchorData.url"
                                class="h-[440rpx] rounded-[20rpx] border-2 border-dashed border-[#BFDBFE] bg-[#F0F6FF] flex flex-col items-center justify-center gap-[16rpx]"
                                @click="handleUploadAnchorVideo">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add2.svg"
                                    class="w-[100rpx] h-[100rpx]" />
                                <text class="text-[28rpx] font-extrabold upload-gradient-text">点击上传训练视频</text>
                                <view class="w-[90%] bg-[#ffffff]/80 rounded-[16rpx] px-[20rpx] py-[14rpx]">
                                    <view class="flex flex-col gap-[10rpx]">
                                        <view class="flex items-start gap-[10rpx]">
                                            <view
                                                class="w-[10rpx] h-[10rpx] rounded-full bg-[#BFDBFE] mt-[8rpx] flex-shrink-0" />
                                            <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed">
                                                时长：{{ commonUploadLimit.videoMinDuration }}-{{
                                                    commonUploadLimit.videoMaxDuration
                                                }}秒，大小≤{{ commonUploadLimit.size }}MB
                                            </text>
                                        </view>
                                        <view class="flex items-start gap-[10rpx]">
                                            <view
                                                class="w-[10rpx] h-[10rpx] rounded-full bg-[#BFDBFE] mt-[8rpx] flex-shrink-0" />
                                            <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed">
                                                分辨率：单边 ≤
                                                {{ commonUploadLimit.maxWidthResolution }}*{{
                                                    commonUploadLimit.maxHeightResolution
                                                }}
                                            </text>
                                        </view>
                                        <view class="flex items-start gap-[10rpx]">
                                            <view
                                                class="w-[10rpx] h-[10rpx] rounded-full bg-[#BFDBFE] mt-[8rpx] flex-shrink-0" />
                                            <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed"
                                                >编码：h264，帧率：25fps</text
                                            >
                                        </view>
                                        <view class="flex items-start gap-[10rpx]">
                                            <view
                                                class="w-[10rpx] h-[10rpx] rounded-full bg-[#BFDBFE] mt-[8rpx] flex-shrink-0" />
                                            <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed"
                                                >格式：{{ SUPPORTED_EXTENSIONS.join(" / ") }}</text
                                            >
                                        </view>
                                    </view>
                                </view>
                            </view>

                            <view v-else class="h-[440rpx] rounded-[20rpx] overflow-hidden relative bg-black">
                                <video
                                    :src="anchorData.url"
                                    :poster="anchorData.pic"
                                    class="w-full h-full object-cover" />
                                <view
                                    class="absolute inset-0 pointer-events-none border border-[#000000]/5 rounded-[20rpx]" />
                            </view>
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center justify-between px-[28rpx] h-[88rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[28rpx] font-extrabold text-[#0D1117]">上传授权视频</text>
                                <text class="text-[#EF4444] text-[28rpx] font-bold">*</text>
                            </view>
                            <view class="flex items-center gap-[12rpx]">
                                <view class="flex bg-[#F0F2F5] rounded-[16rpx] p-[4rpx] gap-[4rpx]">
                                    <view
                                        v-for="(item, index) in ['手动授权', 'AI授权']"
                                        :key="index"
                                        class="h-[52rpx] px-[20rpx] rounded-[12rpx] flex items-center justify-center text-[22rpx] font-semibold transition-all duration-200"
                                        :class="
                                            authIndex === index
                                                ? 'bg-white text-primary shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="authIndex = index">
                                        {{ item }}
                                    </view>
                                </view>
                                <view @click="showAuthHelp = true">
                                    <u-icon name="question-circle-fill" color="#BFDBFE" size="28" />
                                </view>
                            </view>
                        </view>

                        <view class="p-[20rpx]">
                            <template v-if="authIndex === 0">
                                <view
                                    v-if="!authData.url"
                                    class="h-[420rpx] rounded-[20rpx] border-2 border-dashed border-[#BFDBFE] bg-[#F0F6FF] flex flex-col items-center justify-center gap-[16rpx]"
                                    @click="handleUploadAuthVideo">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/add2.svg"
                                        class="w-[100rpx] h-[100rpx]" />
                                    <text class="text-[28rpx] font-extrabold upload-gradient-text"
                                        >点击上传授权视频</text
                                    >
                                    <view class="w-[90%] bg-[#ffffff]/80 rounded-[16rpx] px-[20rpx] py-[14rpx]">
                                        <view class="flex flex-col gap-[10rpx]">
                                            <view class="flex items-start gap-[10rpx]">
                                                <view
                                                    class="w-[10rpx] h-[10rpx] rounded-full bg-[#BFDBFE] mt-[8rpx] flex-shrink-0" />
                                                <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed">
                                                    视频时长：小于{{ AUTH_VIDEO_MAX_DURATION / 60 }}分钟
                                                </text>
                                            </view>
                                            <view class="flex items-start gap-[10rpx]">
                                                <view
                                                    class="w-[10rpx] h-[10rpx] rounded-full bg-[#BFDBFE] mt-[8rpx] flex-shrink-0" />
                                                <text class="text-[22rpx] text-[#9CA3AF] leading-relaxed"
                                                    >视频编码：h264</text
                                                >
                                            </view>
                                            <view class="flex items-start gap-[10rpx]">
                                                <view
                                                    class="w-[10rpx] h-[10rpx] rounded-full bg-[#EF4444] mt-[8rpx] flex-shrink-0" />
                                                <text class="text-[22rpx] text-[#EF4444] leading-relaxed font-semibold"
                                                    >确保本人出镜授权，保证声音清晰</text
                                                >
                                            </view>
                                        </view>
                                    </view>
                                </view>

                                <view v-else>
                                    <view class="h-[420rpx] rounded-[20rpx] overflow-hidden relative bg-black">
                                        <video
                                            :src="authData.url"
                                            :poster="authData.pic"
                                            class="w-full h-full object-cover" />
                                        <view
                                            class="absolute inset-0 pointer-events-none border border-[#000000]/5 rounded-[20rpx]" />
                                    </view>
                                    <view class="flex justify-end mt-[12rpx]">
                                        <view
                                            class="flex items-center gap-[6rpx] h-[56rpx] px-[20rpx] rounded-[14rpx] bg-[#F0F2F5]"
                                            @click="handleUploadAuthVideo">
                                            <u-icon name="reload" color="#4B5563" size="18" />
                                            <text class="text-xs font-semibold text-[#4B5563]">更换视频</text>
                                        </view>
                                    </view>
                                </view>
                            </template>

                            <template v-else>
                                <view
                                    class="bg-[#EBF2FF] rounded-[20rpx] px-[24rpx] py-[20rpx] flex items-start gap-[12rpx]">
                                    <u-icon
                                        name="info-circle-fill"
                                        color="#0065fb"
                                        size="28"
                                        class="flex-shrink-0 mt-[2rpx]" />
                                    <text class="text-xs text-primary leading-relaxed">
                                        AI授权将自动使用您已上传的训练视频生成授权声明视频，按次消耗算力，确认训练视频无误后使用。
                                    </text>
                                </view>
                            </template>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-4 pt-[20rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))] flex items-center gap-[16rpx]">
            <view
                class="h-[96rpx] w-[200rpx] flex items-center justify-center gap-[8rpx] rounded-[24rpx] bg-[#F0F2F5] border border-solid border-[#E5E9F0]"
                @click="showExample = true">
                <u-icon name="play-circle" color="#4B5563" size="28" />
                <text class="font-semibold text-[#4B5563]">拍摄教程</text>
            </view>

            <view
                class="flex-1 h-[96rpx] flex items-center justify-center gap-[8rpx] rounded-[24rpx] relative overflow-hidden transition-all duration-200"
                :class="isCreate ? 'shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]' : 'opacity-60'"
                :style="
                    isCreate ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)' : 'background: #C0C4CC'
                "
                @click="handleCreateAnchor">
                <text class="text-[30rpx] font-extrabold text-white tracking-wide">开始克隆</text>
                <view class="flex items-center gap-[4rpx] bg-[#ffffff]/20 rounded-full px-[14rpx] py-[6rpx]">
                    <text class="text-[22rpx] text-white font-medium">消耗 {{ getToken }} 算力</text>
                </view>
            </view>
        </view>
    </view>

    <u-popup v-model="showCreateStatus" mode="center" border-radius="48" width="85%" :mask-close-able="false">
        <view class="bg-white rounded-[48rpx] px-[40rpx] py-[48rpx] flex flex-col items-center">
            <view
                class="w-[120rpx] h-[120rpx] rounded-full flex items-center justify-center mb-[28rpx] shadow-[0_8rpx_24rpx_rgba(0,0,0,0.12)]"
                :style="
                    isSuccess ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)' : 'background: #FEF2F2'
                ">
                <u-icon
                    :name="isSuccess ? 'checkmark' : 'close'"
                    :color="isSuccess ? '#ffffff' : '#EF4444'"
                    size="44" />
            </view>
            <text class="text-[36rpx] font-extrabold text-[#0D1117] mb-[10rpx]">
                {{ isSuccess ? "创建任务成功" : "创建任务失败" }}
            </text>
            <text v-if="!isSuccess" class="text-[#9CA3AF] text-center leading-relaxed">
                {{ detail.remark || "请检查网络或稍后重试" }}
            </text>
            <view
                class="w-full h-[96rpx] flex items-center justify-center rounded-[24rpx] mt-[40rpx] relative overflow-hidden shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleConfirm">
                <text class="text-[30rpx] font-extrabold text-white">确认</text>
            </view>
        </view>
    </u-popup>

    <popup-bottom v-model="showExample" title="拍摄教程" height="85%" @close="showExample = false">
        <template #content>
            <scroll-view scroll-y class="h-full bg-[#F7F9FC]">
                <view class="px-4 pt-[16rpx] pb-[40rpx] flex flex-col gap-[16rpx]">
                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] h-[80rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[28rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">视频教程</text>
                        </view>
                        <view class="p-[20rpx]">
                            <view class="h-[384rpx] rounded-[20rpx] overflow-hidden relative bg-black">
                                <video-player
                                    :play-icon-size="88"
                                    :poster="`${config.baseUrl}static/images/dh_example_bg2.png`"
                                    :video-url="`${config.baseUrl}static/videos/dh_example2.mp4`" />
                            </view>
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] h-[80rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[28rpx] bg-primary rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">拍摄要求</text>
                        </view>
                        <view class="p-[20rpx]">
                            <image
                                class="w-full rounded-[16rpx]"
                                mode="widthFix"
                                src="@/ai_modules/digital_human/static/images/common/video_upload_temp.png" />
                        </view>
                    </view>

                    <view
                        class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                        <view
                            class="flex items-center gap-[10rpx] px-[28rpx] h-[80rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                            <view class="w-[6rpx] h-[28rpx] bg-[#EF4444] rounded-full" />
                            <text class="text-[28rpx] font-extrabold text-[#0D1117]">错误示例</text>
                        </view>
                        <view class="p-[20rpx]">
                            <view class="grid grid-cols-2 gap-[12rpx]">
                                <view
                                    v-for="(item, i) in [
                                        { src: UploadErrorTip1, label: '遮挡面部' },
                                        { src: UploadErrorTip2, label: '人脸出框' },
                                        { src: UploadErrorTip3, label: '侧脸拍摄' },
                                        { src: UploadErrorTip4, label: '多人出镜' },
                                    ]"
                                    :key="i"
                                    class="bg-[#FEF2F2] rounded-[16rpx] p-[16rpx] flex flex-col items-center gap-[10rpx]">
                                    <image :src="item.src" class="w-[120rpx] h-[120rpx] rounded-[12rpx]" />
                                    <text class="text-[22rpx] text-[#EF4444] font-semibold">{{ item.label }}</text>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>

    <u-popup v-model="showAuthHelp" mode="center" border-radius="20" width="85%">
        <view class="bg-white rounded-[28rpx] px-[40rpx] py-[40rpx]">
            <view class="flex items-center gap-[10rpx] mb-[24rpx]">
                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                <text class="text-[30rpx] font-extrabold text-[#0D1117]">AI授权是什么？</text>
            </view>
            <view class="flex flex-col gap-[16rpx]">
                <view class="bg-[#EBF2FF] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                    <text class="text-[#4B5563] leading-relaxed">
                        启用后，无需自行录制授权视频，系统将自动使用您已上传的训练视频生成一段带口型同步的授权声明视频。
                    </text>
                </view>
                <view class="bg-[#FFF7ED] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                    <text class="text-[#92400E] leading-relaxed">
                        该功能按次收费，每次生成会消耗对应算力/金额。建议在确认训练视频无误后再使用，可减少重复扣费。
                    </text>
                </view>
            </view>
            <view
                class="w-full h-[90rpx] flex items-center justify-center rounded-[20rpx] mt-[32rpx] shadow-[0_6rpx_16rpx_rgba(0,101,251,0.25)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="showAuthHelp = false">
                <text class="text-[28rpx] font-bold text-white">我已知晓</text>
            </view>
        </view>
    </u-popup>

    <upload-loading
        v-if="showUploadProgress"
        :progress="uploadProgressNum"
        :loading-text="loadingText"
        :progress-type="uploadProgressType"
        @cancel="handleUploadCancel" />
    <recharge-popup ref="rechargePopupRef" />
</template>

<script setup lang="ts">
import { batchCloneAnchor } from "@/api/digital_human";
import { CloneModeEnum, ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import requestCancel from "@/utils/request/cancel";
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import config from "@/config";
import { requestAuthorization } from "@/utils/file";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useUpload, commonUploadLimit } from "@/ai_modules/digital_human/hooks/useUpload";
import UploadLoading from "@/ai_modules/digital_human/components/upload-loading/upload-loading.vue";
import UploadErrorTip1 from "@/ai_modules/digital_human/static/images/common/example_error1.png";
import UploadErrorTip2 from "@/ai_modules/digital_human/static/images/common/example_error2.png";
import UploadErrorTip3 from "@/ai_modules/digital_human/static/images/common/example_error3.png";
import UploadErrorTip4 from "@/ai_modules/digital_human/static/images/common/example_error4.png";

const { emit, on } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const appStore = useAppStore();

const anchorData = reactive<any>({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM"),
    pic: "",
    url: "",
    width: 0,
    height: 0,
    anchor_id: "",
});

const authData = reactive<any>({
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM"),
    pic: "",
    url: "",
});
const authIndex = ref(0);
const showAuthHelp = ref(false);

const currCloneMode = ref<CloneModeEnum>(CloneModeEnum.FAST);
const showCloneModeDrop = ref(false);

const parseSceneScore = (scene: TokensSceneEnum) => {
    return parseFloat(userStore.getTokenByScene(scene)?.score) || 0;
};

const fastCloneCost = computed(() => {
    return (
        parseSceneScore(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN) +
        parseSceneScore(TokensSceneEnum.HUMAN_AVATAR_CHANJING)
    );
});

const proCloneCost = computed(() => {
    return fastCloneCost.value + parseSceneScore(TokensSceneEnum.HUMAN_AVATAR_SHANJIAN_PRO);
});

const cloneModeOptions = computed(() => [
    {
        value: CloneModeEnum.FAST,
        name: "极速版",
        desc: "生成速度快、消耗算力低，适合批量克隆",
        cost: fastCloneCost.value,
    },
    {
        value: CloneModeEnum.PRO,
        name: "专业版",
        desc: "唇形与细节还原更逼真，适合品牌 IP 出镜",
        cost: proCloneCost.value,
    },
]);

const currCloneModeOption = computed(() => {
    return (
        cloneModeOptions.value.find((item) => item.value === currCloneMode.value) || cloneModeOptions.value[0]
    );
});

const handleSelectCloneMode = (mode: CloneModeEnum) => {
    currCloneMode.value = mode;
    showCloneModeDrop.value = false;
};

const detail = ref<any>({});
const showCreateStatus = ref(false);
const activePollingEnds = ref<Array<() => void>>([]);

const isSuccess = ref(false);

// 支持的上传格式
const SUPPORTED_EXTENSIONS = ["mp4", "mov"];
// 授权视频最大时长
const AUTH_VIDEO_MAX_DURATION = 120;

// 显示拍摄教程弹框
const showExample = ref(false);

// 上传状态管理
const showUploadProgress = ref(false);
const uploadProgressNum = ref(0);
const uploadProgressType = shallowRef<"video" | "image">();
const loadingText = ref("");

// 充值弹窗
const rechargePopupRef = shallowRef();

// 获取消耗的算力
const getToken = computed(() => {
    const baseCost = currCloneMode.value === CloneModeEnum.PRO ? proCloneCost.value : fastCloneCost.value;
    const authCost =
        authIndex.value === 1 ? parseSceneScore(TokensSceneEnum.AI_SHANJIAN_AUTHORIZED_VIDEO) : 0;
    return baseCost + authCost;
});

const isCreate = computed(() => {
    return anchorData.url && (authIndex.value !== 0 || authData.url);
});

const handleUploadAnchorVideo = () => {
    const { upload } = useUpload({
        size: commonUploadLimit.size,
        sizeType: ["original"],
        widthResolution: [commonUploadLimit.minWidthResolution, commonUploadLimit.maxWidthResolution],
        heightResolution: [commonUploadLimit.minHeightResolution, commonUploadLimit.maxHeightResolution],
        duration: [commonUploadLimit.videoMinDuration, commonUploadLimit.videoMaxDuration],
        extension: SUPPORTED_EXTENSIONS,
        async onSuccess(res) {
            const { url, pic, width, height } = res;
            // 更新表单数据
            anchorData.url = url;
            anchorData.pic = pic;
            anchorData.width = width;
            anchorData.height = height;

            anchorData.name = uni.$u.timeFormat(Date.now(), "yyyymmddhhMM");
            showUploadProgress.value = false;
        },
        onProgress(res) {
            // 更新进度
            uploadProgressType.value = res.type;
            uploadProgressNum.value = res.progress;
            loadingText.value = uploadProgressType.value === "video" ? "视频正在上传中..." : "图片正在上传中...";
            showUploadProgress.value = true;
        },
        onError(err) {
            // 错误处理
            showUploadProgress.value = false;
            uploadProgressNum.value = 0;
            resetNavigationBarColor();
        },
    });
    upload();
};

const handleUploadAuthVideo = () => {
    uni.showActionSheet({
        itemList: ["录制授权视频", "从手机相册选择", "选择历史授权视频"],
        success: async (res) => {
            if (res.tapIndex === 0) {
                const isAuthorized = await requestAuthorization("scope.camera");
                if (!isAuthorized) {
                    uni.$u.toast("您关闭了权限，请前往设置打开权限");
                    return;
                }
                uni.$u.route({
                    url: "/ai_modules/digital_human/pages/anchor_auth_camera/anchor_auth_camera",
                });
            } else if (res.tapIndex === 1) {
                handleUploadAuthVideoAlbum();
            } else if (res.tapIndex === 2) {
                uni.$u.route({
                    url: "/ai_modules/digital_human/pages/anchor_auth_video/anchor_auth_video",
                });
            }
        },
    });
};

/**
 * 处理上传取消
 */
const handleUploadCancel = () => {
    // 取消请求
    requestCancel.remove("/upload/video");
    requestCancel.remove("/upload/image");

    // 重置状态
    showUploadProgress.value = false;
    uploadProgressNum.value = 0;
    loadingText.value = "";
    resetNavigationBarColor();
};

/**
 * 重置导航栏颜色
 */
const resetNavigationBarColor = () => {
    // #ifndef H5
    uni.setNavigationBarColor({
        frontColor: "#000000",
        backgroundColor: "#F9FAFB",
    });
    // #endif
};

const handleUploadAuthVideoAlbum = () => {
    const { upload } = useUpload({
        duration: [1, AUTH_VIDEO_MAX_DURATION],
        extension: SUPPORTED_EXTENSIONS,
        onProgress: (res: any) => {
            uni.showLoading({
                title: "视频上传中",
                mask: true,
            });
        },
        onSuccess: async (res: any) => {
            uni.hideLoading();
            uni.showToast({
                title: "视频上传成功",
                icon: "none",
                duration: 3000,
            });

            authData.pic = res.pic;
            authData.url = res.url;
            authData.width = res.width;
            authData.height = res.height;
        },
        onError: (err: any) => {
            const { type, error } = err;
            uni.hideLoading();
            if (type == "video") {
                uni.showToast({
                    title: error || "视频上传失败",
                    icon: "none",
                    duration: 3000,
                });
            }
        },
    });

    upload();
};

const handleCreateAnchor = async () => {
    if (userTokens.value < getToken.value) {
        rechargePopupRef.value?.open();
        return;
    }

    if (!anchorData.url) {
        uni.$u.toast("请上传形象视频");
        return;
    } else if (authIndex.value === 0 && !authData.url) {
        uni.$u.toast("请上传授权视频");
        return;
    }

    uni.showLoading({
        title: "创建形象中...",
        mask: true,
    });

    try {
        await batchCloneAnchor({
            name: anchorData.name,
            width: anchorData.width,
            height: anchorData.height,
            anchor_url: anchorData.url,
            authorized_url: authIndex.value === 0 ? authData.url : "",
            pic: anchorData.pic,
            authorized_pic: authIndex.value === 0 ? authData.pic : "",
            ai_type: authIndex.value,
            clone_mode: currCloneMode.value,
        });
        uni.hideLoading();
        showCreateStatus.value = true;
        isSuccess.value = true;
    } catch (error: any) {
        isSuccess.value = false;
        uni.hideLoading();
        uni.showToast({
            title: error,
            icon: "none",
            duration: 3000,
        });
    }
};

const handleConfirm = () => {
    if (isSuccess.value) {
        emit("confirm", {
            type: ListenerTypeEnum.CREATE_ANCHOR,
            data: anchorData,
        });
        uni.navigateBack();
    } else {
        // 清空授权信息
        authData.pic = "";
        authData.url = "";
        authData.name = "";
        authData.width = 0;
        authData.height = 0;
        authData.anchor_id = "";
        showCreateStatus.value = false;
    }
};

const getAnchorData = (data: any) => {
    anchorData.name = data.name;
    anchorData.pic = data.pic;
    anchorData.url = data.url;
    anchorData.width = data.width;
    anchorData.height = data.height;
};

const getAuthData = (data: any) => {
    authData.name = data.name;
    authData.pic = data.pic;
    authData.url = data.url;
};

onLoad((options: any) => {
    on("confirm", (result: any) => {
        const { type, data } = result;
        if (type === ListenerTypeEnum.VIDEO_UPLOAD) {
            getAnchorData(data);
        }
        if (type === ListenerTypeEnum.ANCHOR_AUTH || type === ListenerTypeEnum.UPLOAD_AUTH_CAMERA) {
            getAuthData(data);
        }
    });
});

onUnload(() => {
    uni.hideLoading();
    activePollingEnds.value.forEach((endFn) => endFn());
    activePollingEnds.value = [];
});
</script>

<style scoped lang="scss">
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[5rpx] left-0 transition-all duration-500;
}

.upload-text {
    background: linear-gradient(90deg, rgba(71, 213, 159, 1) 0%, rgba(55, 204, 237, 1) 100%);
    font-weight: bold;
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 12rpx;
    font-size: 24rpx;
    color: #888;
    line-height: 1.4;
}

.info-dot {
    width: 8rpx;
    height: 8rpx;
    border-radius: 50%;
    background-color: #ccc;
    margin-top: 10rpx;
    flex-shrink: 0;
}

.clone-chip {
    @apply text-[20rpx] font-bold text-white px-[16rpx] py-[4rpx] rounded-full whitespace-nowrap leading-none;
}

.clone-chip--fast {
    background: linear-gradient(90deg, #2680f7, #3e9bff);
}

.clone-chip--pro {
    background: linear-gradient(90deg, #7b61ff, #a855f7);
}
</style>
