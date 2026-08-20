<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title="新闻体视频"
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
                <view class="px-4 pt-2">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">素材图组</text>
                            <view
                                v-if="formData.materialList.length > 0"
                                class="flex items-center gap-[6rpx] bg-[#EBF2FF] rounded-full px-[16rpx] h-[40rpx] ml-[8rpx]">
                                <u-icon name="clock" size="20" color="#0065fb" />
                                <text class="text-[22rpx] font-bold text-primary">
                                    总时长 {{ getMaterialGroupDuration }}s
                                </text>
                            </view>
                        </view>
                        <view
                            class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-full px-[24rpx] py-[10rpx]"
                            @click="handleEditMaterial()">
                            <u-icon name="plus" size="18" color="#0065fb" />
                            <text class="text-xs font-semibold text-primary">添加图组</text>
                        </view>
                    </view>
                </view>
                <view class="grow min-h-0 mt-4">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4 flex flex-col gap-[16rpx] pb-4" v-if="formData.materialList.length > 0">
                            <view
                                v-for="(group, index) in formData.materialList"
                                :key="index"
                                class="bg-white rounded-[24rpx] p-[24rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                                @click="handleEditMaterial(index)">
                                <view class="flex items-center justify-between mb-[18rpx]">
                                    <view class="flex items-center gap-[10rpx]">
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                            <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                        </view>
                                        <text class="text-[28rpx] font-bold text-[#0D1117]"
                                            >素材图组 {{ index + 1 }}</text
                                        >
                                    </view>
                                    <view class="flex items-center gap-[12rpx]">
                                        <view
                                            class="flex items-center gap-[4rpx] bg-[#F0FDF4] rounded-full px-[14rpx] h-[36rpx]">
                                            <u-icon name="clock" size="20" color="#16A34A" />
                                            <text class="text-[22rpx] font-semibold text-[#16A34A]">
                                                {{ getGroupDuration(group) }}s
                                            </text>
                                        </view>
                                        <view class="flex items-center gap-[6rpx]">
                                            <text class="text-primary font-bold">{{ group.length }}</text>
                                            <text class="text-[#9CA3AF]">个</text>
                                            <u-icon name="arrow-right" color="#C0C4CC" size="20" />
                                        </view>
                                    </view>
                                </view>
                                <view class="grid grid-cols-4 gap-[8rpx]">
                                    <view
                                        v-for="(value, valIndex) in group"
                                        :key="valIndex"
                                        class="h-[156rpx] rounded-[12rpx] overflow-hidden">
                                        <image :src="value.pic" class="w-full h-full" mode="aspectFill" />
                                    </view>
                                </view>
                                <view
                                    class="flex items-center justify-between mt-[20rpx] pt-[16rpx] border-[0] border-t border-solid border-[#F0F2F5]">
                                    <view
                                        class="flex items-center gap-[8rpx]"
                                        @click.stop="handleDeleteMaterial(index)">
                                        <view
                                            class="w-[36rpx] h-[36rpx] rounded-full bg-[#FFF1F2] flex items-center justify-center">
                                            <u-icon name="trash" color="#F56C6C" size="16" />
                                        </view>
                                        <text class="text-xs text-[#F56C6C]">删除</text>
                                    </view>
                                    <view class="flex items-center gap-[6rpx]">
                                        <u-icon name="edit-pen" color="#9CA3AF" size="16" />
                                        <text class="text-xs text-[#9CA3AF]">点击编辑</text>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view v-else class="flex flex-col items-center justify-center h-full px-8 py-[80rpx]">
                            <view class="relative mb-[48rpx]">
                                <view
                                    class="w-[280rpx] h-[280rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                    <view
                                        class="w-[200rpx] h-[200rpx] rounded-full bg-[#DBEAFE] flex items-center justify-center">
                                        <view
                                            class="w-[120rpx] h-[120rpx] rounded-[32rpx] flex items-center justify-center shadow-[0_8rpx_24rpx_rgba(0,101,251,0.25)]"
                                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                            <u-icon name="photo" color="#fff" size="44" />
                                        </view>
                                    </view>
                                </view>
                                <view
                                    class="absolute top-[8rpx] right-[-12rpx] w-[56rpx] h-[56rpx] rounded-full bg-[#F0FDF4] shadow-[0_4rpx_12rpx_rgba(0,0,0,0.06)] flex items-center justify-center">
                                    <u-icon name="play-right-fill" color="#16A34A" size="18" />
                                </view>
                                <view
                                    class="absolute bottom-[8rpx] left-[-12rpx] w-[56rpx] h-[56rpx] rounded-full bg-[#FEF9C3] shadow-[0_4rpx_12rpx_rgba(0,0,0,0.08)] flex items-center justify-center">
                                    <u-icon name="grid" color="#D97706" size="22" />
                                </view>
                            </view>

                            <text class="text-[34rpx] font-extrabold text-[#0D1117] mb-[16rpx]">还没有素材图组</text>
                            <text class="text-[#9CA3AF] text-center leading-relaxed mb-[64rpx]">
                                添加图片素材，AI 将自动混剪生成精彩视频
                            </text>

                            <view
                                class="flex items-center gap-[10rpx] h-[96rpx] px-[64rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                @click="handleEditMaterial()">
                                <u-icon name="plus" size="24" color="#fff" />
                                <text class="text-[30rpx] font-extrabold text-white">添加图组</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view v-show="step === 2" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="p-4 pt-2 space-y-2">
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
                <view class="p-4 pt-2 space-y-2">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">文案内容</text>
                        </view>
                        <text class="text-xs text-[#9CA3AF]">
                            共
                            <text class="text-primary font-bold">{{ formData.copywriterList.length }}</text>
                            条
                        </text>
                    </view>
                    <view class="flex gap-[12rpx]">
                        <view
                            class="flex-1 flex items-center justify-center gap-[10rpx] h-[96rpx] rounded-[24rpx] bg-white border border-solid border-[#E5E9F0] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.04)]"
                            @click="handleShowCopywriter()">
                            <u-icon name="edit-pen" color="#4B5563" size="22" />
                            <text class="text-[28rpx] font-bold text-[#334155]">手动输入</text>
                        </view>
                        <view
                            class="flex-1 h-[96rpx] flex items-center justify-center gap-[10rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_20rpx_rgba(0,101,251,0.25)]"
                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                            @click="showChooseAgent = true">
                            <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]" />
                            <text class="text-[28rpx] font-bold text-white">AI 智能生成</text>
                        </view>
                    </view>
                </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full" v-if="formData.copywriterList.length > 0">
                        <view class="px-4 flex flex-col gap-[16rpx] pb-4">
                            <view
                                v-for="(item, index) in formData.copywriterList"
                                :key="index"
                                class="relative bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                                :class="!isSingleCopywriterValid(item) ? 'border border-solid border-[#F56C6C]' : ''"
                                @click="handleSelectCopywriter(index)">
                                <view class="absolute left-0 top-0 w-[6rpx] h-full bg-primary rounded-l-[24rpx]" />
                                <view class="pl-[32rpx] pr-[24rpx] pt-[22rpx] pb-[18rpx]">
                                    <view class="flex items-center gap-[12rpx] mb-[18rpx]">
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                            <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                        </view>
                                        <text class="flex-1 text-[28rpx] font-bold text-[#0D1117] truncate"
                                            >文案 {{ index + 1 }}</text
                                        >
                                        <view
                                            class="w-[40rpx] h-[40rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                                            @click.stop="handleDeleteCopywriter(index)">
                                            <u-icon name="close" color="#9CA3AF" size="14" />
                                        </view>
                                    </view>
                                    <text class="text-[#4B5563] leading-relaxed">{{ item }}</text>
                                    <view
                                        class="flex items-center justify-between mt-[14rpx] pt-[20rpx] border-[0] border-t border-solid border-[#F0F2F5]">
                                        <view
                                            class="flex items-center gap-[6rpx]"
                                            v-if="!isSingleCopywriterValid(item)">
                                            <u-icon name="info-circle-fill" color="#F56C6C" size="14" />
                                            <text class="text-[22rpx] text-[#F56C6C]">
                                                {{
                                                    item.length > NEWS_BODY_COPYWRITER_LIMIT
                                                        ? `超出${NEWS_BODY_COPYWRITER_LIMIT}字限制`
                                                        : "不能少于3个字"
                                                }}
                                            </text>
                                        </view>
                                        <view v-else />
                                        <text
                                            class="text-[22rpx]"
                                            :class="
                                                item.length > NEWS_BODY_COPYWRITER_LIMIT
                                                    ? 'text-[#F56C6C] font-bold'
                                                    : 'text-[#C0C4CC]'
                                            ">
                                            {{ item.length }} / {{ NEWS_BODY_COPYWRITER_LIMIT }}
                                        </text>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                    <copywriter-empty v-else />
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
                                <text class="text-[28rpx] font-bold text-[#0D1117]">作品名称</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                                    <u-input
                                        v-model="formData.name"
                                        maxlength="50"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="请输入作品名称" />
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
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="handleStep(1)">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">参考素材</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.materialList.length }}</text>
                                    <text class="text-[#9CA3AF]">个</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
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
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">文案内容</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.copywriterList.length }}</text>
                                    <text class="text-[#9CA3AF]">条</text>
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
                                <text class="text-xs text-[#9CA3AF]">每条新闻体生成视频的数量</text>
                            </view>
                            <view class="flex items-center gap-[16rpx]">
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                    @click="handleMinusVideoCount('minus')">
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
                                    @click="handleMinusVideoCount('add')">
                                    <text class="text-[32rpx] text-primary font-bold leading-none">＋</text>
                                </view>
                            </view>
                        </view>

                        <view
                            class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] flex items-center justify-between shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view>
                                <text class="text-[28rpx] font-bold text-[#0D1117] block mb-[6rpx]">新闻体时长</text>
                                <text class="text-xs text-[#9CA3AF]">单条新闻体视频的时长（5–300 秒）</text>
                            </view>
                            <view class="flex items-center gap-[16rpx]">
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                    @click="handleNewsDurationStep('minus')">
                                    <text class="text-[32rpx] text-primary font-bold leading-none">−</text>
                                </view>
                                <view
                                    class="w-[128rpx] h-[56rpx] bg-[#EBF2FF] rounded-[14rpx] flex items-center justify-center gap-[4rpx]">
                                    <u-input
                                        v-model="formData.extra.videoDuration"
                                        type="number"
                                        placeholder=""
                                        :custom-style="{
                                            width: '64rpx',
                                            color: '#0065fb',
                                            fontWeight: '800',
                                            fontSize: '30rpx',
                                        }"
                                        input-align="center"
                                        @blur="handleNewsDurationBlur" />
                                    <text class="text-[22rpx] text-[#0065fb] font-bold leading-none">秒</text>
                                </view>
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                    @click="handleNewsDurationStep('add')">
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
                                        v-for="(item, index) in ['按顺序', '随机']"
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
                                        v-for="(item, index) in ['随机', '手动选择']"
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
                                    MontageStylesType.NEWS
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

                        <view class="h-[20rpx]" />
                    </view>
                </scroll-view>
            </view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]">
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
                    已添加
                </text>
            </view>
            <view
                v-else-if="step < steps.length"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-white"
                @click="handleStep(step, 'prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>
            <view v-else class="flex flex-col items-center gap-[6rpx] px-[16rpx]" @click="showTokensCost = true">
                <image src="@/ai_modules/digital_human/static/icons/star.svg" class="w-[36rpx] h-[36rpx]" />
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
        <confirm-dialog
            v-if="confirmDialogVisible"
            v-model="confirmDialogVisible"
            confirm-text="删除"
            center
            content="是否确定删除该图组？"
            @confirm="handleDeleteMaterialConfirm" />
        <choose-agent
            v-if="showChooseAgent"
            v-model="showChooseAgent"
            :system-agent-ids="[2, 6]"
            @select="handleSelectAgent" />
        <choose-character v-if="showCharacter" v-model="showCharacter" @select="handleSelectCharacter" />
        <video-preview
            v-if="showVideoPreview"
            v-model="showVideoPreview"
            :video-url="playItem.url"
            :poster="playItem.pic" />
        <create-success-pop
            v-if="showCreateSuccess"
            v-model="showCreateSuccess"
            title="视频生成中"
            desc="您可以立即去设置发布任务，也可以等待视频生成成功后再发布"
            @to="toPublish"
            @seek="toRecord" />
        <tokens-cost v-if="showTokensCost" v-model="showTokensCost" :type="MontageTypeEnum.NEWS_BODY" />
        <recharge-popup ref="rechargePopupRef" />
    </view>
</template>

<script setup lang="ts">
import { ListenerTypeEnum, MontageTypeEnum, MontageStylesType } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import Steps from "@/ai_modules/digital_human/components/steps/steps.vue";
import ChooseCharacter from "@/ai_modules/digital_human/components/choose-character/choose-character.vue";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";
import CopywriterEmpty from "@/ai_modules/digital_human/components/copywriter-empty/copywriter-empty.vue";
import VideoCoverUpload from "@/ai_modules/digital_human/components/video-cover-upload/video-cover-upload.vue";

import { useNewsBodySteps, NEWS_BODY_COPYWRITER_LIMIT } from "./hooks/useSteps";
import { useMaterialGroup } from "./hooks/useMaterialGroup";
import { useNewsBodyCopywriter } from "./hooks/useCopywriter";
import { usePersona } from "../../hooks/usePersona";
import { useGenerateSetting } from "./hooks/useGenerateSetting";

const { on } = useEventBusManager();

// ─── 表单数据 ────────────────────────────────────────────────
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
        clip: number;
        music: number;
        video_count: number;
        videoDuration: number;
    };
    audio: any[];
    clip: any[];
    cover: string;
}>({
    anchorLists: [],
    copywriterList: [],
    materialList: [],
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "新闻体视频",
    person_name: "",
    person_introduction: "",
    shanjian_type: MontageTypeEnum.NEWS_BODY,
    music: [],
    extra: {
        ai_music: true,
        volume: 0.5,
        clip: 0,
        music: 0,
        video_count: 1,
        videoDuration: 10,
    },
    audio: [],
    clip: [],
    cover: "",
});

// ─── Step 导航 ───────────────────────────────────────────────
const { steps, step, canNext, handleStep, isSingleCopywriterValid } = useNewsBodySteps(formData);

// ─── Step 1：图组管理 ─────────────────────────────────────────────
const {
    confirmDialogVisible,
    getMaterialGroupDuration,
    getGroupDuration,
    handleEditMaterial,
    handleDeleteMaterial,
    handleDeleteMaterialConfirm,
    onMaterialGroupConfirm,
} = useMaterialGroup({ formData });

// ─── Step2 人设 ──────────────────────────────────────────────
const { showCharacter, isCharacter, handleSelectCharacter } = usePersona({ formData });

// ─── Step3 文案 ──────────────────────────────────────────────
const {
    showChooseAgent,
    handleSelectCopywriter,
    handleShowCopywriter,
    handleDeleteCopywriter,
    handleSelectAgent,
    onCopywriterConfirm,
} = useNewsBodyCopywriter(formData);

// ─── Step4 生成设置 ──────────────────────────────────────────
const {
    showTokensCost,
    showCreateSuccess,
    rechargePopupRef,
    handleMinusVideoCount,
    handleNewsDurationStep,
    handleNewsDurationBlur,
    handleCreateVideo,
    toPublish,
    toRecord,
} = useGenerateSetting(formData);

// ─── 视频预览 ────────────────────────────────────────────────
const playItem = reactive({ pic: "", url: "" });
const showVideoPreview = ref(false);

// ─── 事件总线 ────────────────────────────────────────────────
onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;

        if (type === ListenerTypeEnum.MONTAGE_COPYWRITER || type === ListenerTypeEnum.AI_COPYWRITER) {
            onCopywriterConfirm(type, data);
            return;
        }

        if (type === ListenerTypeEnum.MONTAGE_MATERIAL_GROUP) {
            onMaterialGroupConfirm(data);
        }

        if (type === ListenerTypeEnum.CHOOSE_MUSIC) {
            if (data.music === -1) {
                formData.extra.ai_music = true;
                formData.music = [];
            } else {
                formData.music = data.music;
                formData.extra.ai_music = false;
            }
            formData.extra.volume = data.volume;
            return;
        }

        if (type === ListenerTypeEnum.CHOOSE_VIDEO_STYLES) {
            if (data.length === 0) return;
            formData.clip = data;
        }
    });
});
</script>
