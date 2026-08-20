<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title="真人口播视频混剪"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: '#ffffff' }" />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="steps" :step="step" @step="handleStep" />
        </view>

        <view class="grow min-h-0 relative">
            <view v-show="step === 1" class="h-full flex flex-col">
                <view class="p-4 pt-2 space-y-2">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">上传口播视频</text>
                        </view>
                        <text class="text-xs text-[#9CA3AF]">
                            已上传
                            <text class="text-primary font-bold">{{ formData.anchorLists.length }}</text>
                            个
                        </text>
                    </view>
                    <view class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                        <u-icon name="info-circle" color="#0065fb" size="20" />
                        <text class="text-xs text-primary font-medium">支持多个口播视频，系统将自动混剪生成</text>
                    </view>
                </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="grid grid-cols-3 gap-[16rpx] px-4">
                            <view
                                v-for="(item, index) in formData.anchorLists"
                                :key="index"
                                class="relative rounded-[24rpx] overflow-hidden aspect-[3/4]">
                                <image :src="item.pic" class="w-full h-full" mode="aspectFill" />
                                <view
                                    class="absolute bottom-0 left-0 right-0 h-[120rpx]"
                                    style="background: linear-gradient(to top, rgba(0, 0, 0, 0.45), transparent)" />
                                <view
                                    class="absolute bottom-[12rpx] right-[12rpx] w-[44rpx] h-[44rpx] rounded-full bg-[#ffffff]/30 flex items-center justify-center border border-solid border-[#ffffff]/40"
                                    @click.stop="handleAnchorVideoPlay(item)">
                                    <u-icon name="play-right-fill" color="#ffffff" size="20" class="ml-0.5" />
                                </view>
                                <view
                                    class="absolute top-[12rpx] right-[12rpx] w-[40rpx] h-[40rpx] rounded-full bg-[#000000]/40 flex items-center justify-center"
                                    @click="handleDeleteAnchor(index)">
                                    <u-icon name="close" color="#ffffff" size="14" />
                                </view>
                                <view class="absolute bottom-[12rpx] left-[12rpx]">
                                    <view
                                        class="px-[12rpx] py-[6rpx] text-white text-[22rpx] rounded-full border border-solid border-[#ffffff]/40 bg-[#000000]/30"
                                        @click="handleReplaceAnchor(index)">
                                        替换
                                    </view>
                                </view>
                            </view>
                            <view
                                class="bg-white aspect-[3/4] rounded-[20rpx] border-2 border-dashed border-[#0065fb]/30 bg-[#F0F6FF] flex flex-col items-center justify-center gap-[10rpx]"
                                @click="chooseAnchorUploadType()">
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                    <u-icon name="plus" color="#0065fb" size="28" />
                                </view>
                                <text class="text-xs text-primary font-semibold">添加视频</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view v-show="step === 2" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pt-2 space-y-2">
                        <view class="flex items-center justify-between h-[80rpx]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[32rpx] font-extrabold text-[#0D1117]">身份人设</text>
                            </view>
                            <view
                                class="flex items-center gap-[8rpx] bg-white border border-solid border-[#E5E9F0] rounded-full px-[20rpx] py-[10rpx]"
                                @click="showCharacter = true">
                                <u-icon name="clock" color="#4B5563" size="20" />
                                <text class="text-xs font-semibold text-[#4B5563]">历史人设</text>
                            </view>
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117] flex-1">人设名称</text>
                                <text class="text-[22rpx] text-[#9CA3AF]">最多 20 字</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                                    <u-input
                                        v-model="formData.person_name"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="如：小美、助理小李、专业顾问"
                                        maxlength="20"
                                        @change="isCharacter = false" />
                                </view>
                            </view>
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117] flex-1">人设介绍</text>
                                <text class="text-[22rpx] text-[#9CA3AF]">最多 50 字</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                                    <u-input
                                        v-model="formData.person_introduction"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="如：专业美妆博主，擅长护肤推荐，风格亲切自然"
                                        maxlength="50"
                                        type="textarea"
                                        height="80"
                                        @change="isCharacter = false" />
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <view v-show="step === 3" class="h-full flex flex-col">
                <view class="px-4 pt-2">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">参考素材</text>
                        </view>
                        <text class="text-[22rpx] text-[#9CA3AF]"
                            >限制{{ montageConfig.materialTotalDuration }}分钟</text
                        >
                    </view>
                    <material-duration-bar ref="materialDurationBarRef" :material-list="formData.materialList" />
                </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="p-4">
                            <material-container
                                :material-list="formData.materialList"
                                @preview="previewMaterial"
                                @replace="handleReplaceMaterial"
                                @delete="handleDeleteMaterial"
                                @upload="chooseMaterialUploadType()" />
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view v-show="step === 4" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="p-4 pt-2 space-y-3">
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
                                <text class="text-[28rpx] font-bold text-[#0D1117]">视频名称</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                                    <u-input
                                        v-model="formData.name"
                                        maxlength="50"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="请输入视频名称" />
                                </view>
                            </view>
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <video-cover-upload v-model="formData.cover" />
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117]">内容汇总</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="flex items-center justify-between mb-[14rpx]">
                                    <text class="text-xs text-[#9CA3AF] font-semibold">口播视频</text>
                                    <view class="flex items-center gap-[4rpx]" @click="handleStep(1)">
                                        <text class="text-xs text-primary font-semibold"
                                            >{{ formData.anchorLists.length }} 个</text
                                        >
                                        <u-icon name="arrow-right" size="20" color="#0065fb" />
                                    </view>
                                </view>
                                <scroll-view scroll-x>
                                    <view class="flex gap-[12rpx] pb-[4rpx]">
                                        <view
                                            v-for="(item, index) in formData.anchorLists"
                                            :key="index"
                                            class="flex-shrink-0 w-[100rpx] h-[136rpx] rounded-[16rpx] overflow-hidden shadow-[0_2rpx_8rpx_rgba(0,0,0,0.10)]">
                                            <image :src="item.pic" class="w-full h-full" mode="aspectFill" />
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="handleStep(2)">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">身份人设</text>
                                <view class="flex items-center gap-[6rpx]">
                                    <text
                                        class=""
                                        :class="formData.person_name ? 'text-primary font-semibold' : 'text-[#9CA3AF]'">
                                        {{ formData.person_name || "未设置" }}
                                    </text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="handleStep(3)">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">参考素材</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.materialList.length }}</text>
                                    <text class="text-[#9CA3AF]">个</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                            <navigator
                                :url="`/ai_modules/digital_human/pages/music_choose/music_choose?music=${JSON.stringify(
                                    formData.music,
                                )}&volume=${formData.extra.volume}&ai_music=${formData.extra.ai_music}`"
                                hover-class="none"
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx]">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">背景音乐</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <template v-if="formData.music.length > 0">
                                        <text class="text-[#9CA3AF]">共</text>
                                        <text class="text-primary font-bold">{{ formData.music.length }}</text>
                                        <text class="text-[#9CA3AF]">个</text>
                                    </template>
                                    <text v-else-if="formData.extra.ai_music" class="text-[#9CA3AF]">AI 音乐库</text>
                                    <text v-else class="text-[#9CA3AF]">无</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </navigator>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] flex items-center justify-between shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view>
                                <text class="text-[28rpx] font-bold text-[#0D1117] block mb-[6rpx]">生成视频数量</text>
                                <text class="text-xs text-[#9CA3AF]">每条口播素材生成的视频数量</text>
                            </view>
                            <view class="flex items-center gap-[16rpx]">
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                    @click="handleVideoCount('minus')">
                                    <text class="text-[32rpx] text-primary font-bold leading-none">−</text>
                                </view>
                                <view
                                    class="w-[72rpx] h-[56rpx] bg-[#EBF2FF] rounded-[14rpx] flex items-center justify-center">
                                    <u-input
                                        v-model="formData.extra.video_count"
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
                                    @click="handleVideoCount('add')">
                                    <text class="text-[32rpx] text-primary font-bold leading-none">＋</text>
                                </view>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117]">使用设置</text>
                            </view>
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[104rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-semibold text-[#0D1117]">背景音乐使用</text>
                                <view class="flex bg-[#F0F2F5] rounded-[16rpx] p-[4rpx] w-[240rpx]">
                                    <view
                                        v-for="(item, index) in ORDER_MODE_TABS"
                                        :key="index"
                                        class="flex-1 h-[56rpx] rounded-[12rpx] flex items-center justify-center text-[22rpx] font-semibold transition-all duration-200"
                                        :class="
                                            index === formData.extra.music
                                                ? 'bg-white text-primary shadow-[0_2rpx_6rpx_rgba(0,0,0,0.08)]'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="formData.extra.music = index">
                                        {{ item }}
                                    </view>
                                </view>
                            </view>
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[104rpx]"
                                :class="
                                    formData.extra.clip === 1 ? 'border-[0] border-b border-solid border-[#F0F2F5]' : ''
                                ">
                                <text class="text-[28rpx] font-semibold text-[#0D1117]">视频风格</text>
                                <view class="flex bg-[#F0F2F5] rounded-[16rpx] p-[4rpx] w-[240rpx]">
                                    <view
                                        v-for="(item, index) in CLIP_MODE_TABS"
                                        :key="index"
                                        class="flex-1 h-[56rpx] rounded-[12rpx] flex items-center justify-center text-[22rpx] font-semibold transition-all duration-200"
                                        :class="
                                            index === formData.extra.clip
                                                ? 'bg-white text-primary shadow-[0_2rpx_6rpx_rgba(0,0,0,0.08)]'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="formData.extra.clip = index">
                                        {{ item }}
                                    </view>
                                </view>
                            </view>
                            <navigator
                                v-if="formData.extra.clip === 1"
                                :url="`/ai_modules/digital_human/pages/montage_styles_choose/montage_styles_choose?type=${
                                    MontageStylesType.REAL_PERSON
                                }&data=${JSON.stringify(formData.clip)}`"
                                hover-class="none"
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx]">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">选择视频风格</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.clip.length }}</text>
                                    <text class="text-[#9CA3AF]">个</text>
                                    <u-icon name="arrow-right" size="13" color="#C0C4CC" />
                                </view>
                            </navigator>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-t border-solid border-[#F0F2F5] px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]">
            <view
                v-if="step === 1"
                class="w-[100rpx] h-[96rpx] rounded-[20rpx] flex flex-col items-center justify-center transition-all duration-300"
                :class="formData.anchorLists.length > 0 ? 'bg-[#EBF2FF]' : 'bg-[#F0F2F5]'">
                <text
                    class="text-[32rpx] font-extrabold leading-none"
                    :class="formData.anchorLists.length > 0 ? 'text-primary' : 'text-[#C0C4CC]'">
                    {{ formData.anchorLists.length }}
                </text>
                <text
                    class="text-[20rpx] mt-[4rpx]"
                    :class="formData.anchorLists.length > 0 ? 'text-[#0065fb]/70' : 'text-[#C0C4CC]'">
                    已上传
                </text>
            </view>
            <view
                v-else-if="step < steps.length"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-white"
                @click="handleStep(step, 'prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>
            <view v-else class="flex flex-col items-center gap-[6rpx] px-[16rpx]" @click="showTokensCost = true">
                <image src="@/ai_modules/digital_human/static/icons/star.svg" class="w-[36rpx] h-[36rpx]"></image>
                <text class="text-[20rpx] text-[#9CA3AF] font-medium">算力消耗</text>
            </view>

            <view
                v-if="step < steps.length"
                class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] transition-all duration-300"
                :class="canNext ? 'bg-primary shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : 'bg-[#E5E7EB]'"
                @click="handleStep(step, 'next')">
                <text class="text-[30rpx] font-bold" :class="canNext ? 'text-white' : 'text-[#9CA3AF]'">下一步</text>
            </view>
            <view
                v-else
                class="flex-1 h-[100rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleCreateVideo">
                <text class="text-[32rpx] font-extrabold text-white tracking-wide">立即生成视频</text>
            </view>
        </view>

        <choose-history
            v-model="showAnchorHistory"
            type="video"
            :limit="anchorReplaceIndex === -1 ? 9 : 1"
            @select="handleSelectAnchorHistory" />

        <choose-history
            v-model="showMaterialHistory"
            :limit="replaceMaterialIndex === -1 ? 9 : 1"
            @select="handleSelectMaterialHistory" />

        <upload-category-panel v-model="showUploadCategoryPanel" @select="handleSelectCategory" />
        <choose-character v-if="showCharacter" v-model="showCharacter" @select="handleSelectCharacter" />
        <choose-material
            v-model="showMaterialLibrary"
            :limit="uploadMaterialType === 'image' || replaceMaterialIndex === -1 ? 9 : 1"
            :type="uploadMaterialType"
            :mode="uploadMaterialMode"
            @select="handleSelectMaterial" />

        <upload-rule-pop
            v-if="showAnchorUploadTip"
            v-model="showAnchorUploadTip"
            @handle-upload="uploadAndProcessFilesForAnchor('video')" />

        <upload-rule-pop
            v-if="showMaterialUploadTip"
            v-model="showMaterialUploadTip"
            @handle-upload="uploadAndProcessFiles(uploadMaterialType)" />

        <upload-progress
            v-if="showAnchorUploadProgress"
            v-model="showAnchorUploadProgress"
            :upload-list="anchorUploadList" />

        <upload-progress
            v-if="showMaterialUploadProgress"
            v-model="showMaterialUploadProgress"
            :upload-list="materialUploadList" />
        <video-preview
            v-if="showVideoPreviewForAnchor"
            v-model="showVideoPreviewForAnchor"
            :video-url="videoPreviewForAnchor.url"
            :poster="videoPreviewForAnchor.poster"
            @update:show="showVideoPreviewForAnchor = false" />
        <video-preview
            v-if="sharedVideoPreview.show"
            v-model="sharedVideoPreview.show"
            :video-url="sharedVideoPreview.url"
            :poster="sharedVideoPreview.poster"
            @update:show="sharedVideoPreview.show = false" />

        <create-success-pop
            v-if="showCreateSuccess"
            v-model="showCreateSuccess"
            title="视频生成中"
            desc="您可以立即去设置发布任务，也可以等待视频生成成功后再发布"
            @to="toPublish"
            @seek="toRecord" />
        <tokens-cost v-if="showTokensCost" v-model="showTokensCost" :type="MontageTypeEnum.REAL_PERSON_AI" />
        <recharge-popup ref="rechargePopupRef" />
    </view>
</template>

<script setup lang="ts">
import { MontageTypeEnum, MontageStylesType, ListenerTypeEnum } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import Steps from "@/ai_modules/digital_human/components/steps/steps.vue";
import MaterialDurationBar from "@/ai_modules/digital_human/components/material-duration-bar/material-duration-bar.vue";
import MaterialContainer from "@/ai_modules/digital_human/components/material-container/material-container.vue";
import UploadRulePop from "@/ai_modules/digital_human/components/upload-rule-pop/upload-rule-pop.vue";
import ChooseCharacter from "@/ai_modules/digital_human/components/choose-character/choose-character.vue";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import VideoCoverUpload from "@/ai_modules/digital_human/components/video-cover-upload/video-cover-upload.vue";

// ─── 复用 Hooks ──────────────────────────────────────────────────
import { useSteps } from "./hooks/useSteps";
import { usePersona } from "../../hooks/usePersona";
import { useMaterialStep } from "../../hooks/useMaterialStep";
import { useGenerateSetting, ORDER_MODE_TABS, CLIP_MODE_TABS } from "./hooks/useGenerateSetting";
import { useRealPersonAnchor } from "./hooks/useRealPersonAnchor";

// ─── 表单数据 ─────────────────────────────────────────────────────
const formData = reactive<{
    anchorLists: any[];
    copywriterList: any[];
    materialList: any[];
    name: string;
    person_name: string;
    person_introduction: string;
    shanjian_type: MontageTypeEnum;
    music: any[];
    extra: {
        ai_music: boolean;
        volume: number;
        music: number;
        clip: number;
        video_count: number;
    };
    audio: any[];
    clip: any[];
    cover: string;
}>({
    anchorLists: [],
    copywriterList: [],
    materialList: [],
    name: `${uni.$u.timeFormat(Date.now(), "yyyymmddhhMM")}真人口播视频智剪`,
    person_name: "",
    person_introduction: "",
    shanjian_type: MontageTypeEnum.REAL_PERSON_AI,
    music: [],
    extra: {
        ai_music: true,
        volume: 0.5,
        music: 0,
        clip: 0,
        video_count: 1,
    },
    audio: [],
    clip: [],
    cover: "",
});

// ─── Refs ─────────────────────────────────────────────────────────
const rechargePopupRef = shallowRef();

/**
 * 统一视频预览状态（口播 & 素材共用同一个弹窗）
 * 通过 sharedVideoPreview 传入两个 hook，避免各自维护独立状态造成冲突
 */
const sharedVideoPreview = reactive({
    show: false,
    url: "",
    poster: "",
});

// ─── Step 3：参考素材 ─────────────────────────────────────────────
const {
    montageConfig,
    showUploadCategoryPanel,
    showMaterialLibrary,
    showChooseHistory: showMaterialHistory,
    showUploadTip: showMaterialUploadTip,
    uploadMaterialType,
    uploadMaterialMode,
    replaceMaterialIndex,
    showUploadProgress: showMaterialUploadProgress,
    uploadMaterialList: materialUploadList,
    uploadAndProcessFiles,
    getMaterialTotalDuration,
    handleSelectCategory,
    chooseUploadType: chooseMaterialUploadType,
    handleSelectMaterial,
    handleSelectHistory: handleSelectMaterialHistory,
    previewMaterial,
    handleReplaceMaterial,
    handleDeleteMaterial,
} = useMaterialStep({
    formData,
});

// ─── Step 1：口播视频 ─────────────────────────────────────────────
const {
    showHistory: showAnchorHistory,
    showUploadTip: showAnchorUploadTip,
    uploadMaterialType: anchorUploadType,
    uploadAndProcessFiles: uploadAndProcessFilesForAnchor,
    showUploadProgress: showAnchorUploadProgress,
    uploadMaterialList: anchorUploadList,
    replaceMaterialIndex: anchorReplaceIndex,
    videoPreview: videoPreviewForAnchor,
    showVideoPreview: showVideoPreviewForAnchor,
    handleVideoPlay: handleAnchorVideoPlay,
    handleDeleteAnchor,
    handleReplaceAnchor,
    chooseAnchorUploadType,
    handleSelectHistory: handleSelectAnchorHistory,
} = useRealPersonAnchor({
    formData,
});

// ─── Step 2：人设 ─────────────────────────────────────────────────
const { showCharacter, isCharacter, handleSelectCharacter } = usePersona({ formData });

// ─── 步骤导航 ─────────────────────────────────────────────────────
const { step, steps, canNext, handleStep } = useSteps({
    formData,
    getMaterialTotalDuration,
});

// ─── Step 4：生成设置 ─────────────────────────────────────────────
const { showCreateSuccess, showTokensCost, handleVideoCount, handleCreateVideo, toPublish, toRecord } =
    useGenerateSetting({
        formData,
        rechargePopupRef,
        redirectParams: (id: number) => ({
            task_id: JSON.stringify([id]),
            scene: 1,
            type: formData.shanjian_type,
        }),
        toRecordParams: { source: "1", type: 3 },
    });

// ─── EventBus ────────────────────────────────────────────────────
const { on } = useEventBusManager();

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CHOOSE_MUSIC) {
            if (data.music === -1) {
                formData.extra.ai_music = true;
                formData.music = [];
            } else {
                formData.music = data.music;
                formData.extra.ai_music = false;
            }
            formData.extra.volume = data.volume;
        }
        if (type === ListenerTypeEnum.CHOOSE_VIDEO_STYLES) {
            if (!data.length) return;
            formData.clip = data;
        }
    });
});
</script>
