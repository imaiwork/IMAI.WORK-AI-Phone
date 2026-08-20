<template>
    <view class="grid grid-cols-1 gap-[16rpx]">
        <view
            v-for="feature in leadFeatureList"
            :key="feature.key"
            class="feat-card"
            :class="{ on: leadFeatureStatus[feature.key] }">
            <view class="lfc-hd" @click.stop="toggleLeadFeature(feature.key)">
                <view
                    class="w-[72rpx] h-[72rpx] rounded-[24rpx] flex items-center justify-center shrink-0"
                    :style="{ background: feature.iconBg }">
                    <image :src="feature.icon" mode="aspectFit" class="w-[32rpx] h-[32rpx]" />
                </view>
                <view class="flex-1 min-w-0">
                    <text class="block text-[28rpx] font-bold text-[#1F2937]">
                        {{ feature.title }}
                    </text>
                    <text class="block text-[24rpx] text-[#9CA3AF] mt-[2rpx] line-clamp-1">
                        {{ feature.desc }}
                    </text>
                </view>
                <view class="shrink-0" @click.stop>
                    <u-switch v-model="leadFeatureStatus[feature.key]" :size="34" inactive-color="#E5E7EB" />
                </view>
                <u-icon
                    name="arrow-right"
                    color="#C0C8D8"
                    size="22"
                    :custom-style="{
                        transform: openLeadFeature.includes(feature.key) ? 'rotate(90deg)' : 'rotate(0deg)',
                        transition: 'transform 0.28s cubic-bezier(.4,0,.2,1)',
                    }"></u-icon>
            </view>

            <view v-if="openLeadFeature.includes(feature.key)" class="lfc-inner">
                <!-- wxv 视频号获客 -->
                <template v-if="feature.key === 'wxv'">
                    <text class="lfc-sec-lbl">线索关键词</text>
                    <view class="detail-kw-wrap mb-[16rpx]">
                        <view
                            v-for="(word, index) in wxvKeywords"
                            :key="word"
                            class="detail-kw-tag editable"
                            @click.stop="openWxvKeywordPopup(index)">
                            <text>{{ word }}</text>
                            <view class="rm" @click.stop="removeKeyword(wxvKeywords, word)">
                                <u-icon name="close" color="#9CA3AF" size="16"></u-icon>
                            </view>
                        </view>
                        <button class="plain-btn detail-kw-add" @click.stop="openWxvKeywordPopup()">+ 添加</button>
                        <button
                            v-if="wxvMoreWords.length && !showWxvMore"
                            class="plain-btn detail-kw-more"
                            @click.stop="showWxvMore = true">
                            {{ `+${wxvMoreWords.length} 个` }}
                            <u-icon name="arrow-down" color="#6B7280" size="18"></u-icon>
                        </button>
                    </view>
                    <view v-if="showWxvMore" class="detail-kw-wrap mb-[24rpx]">
                        <view
                            v-for="(word, index) in wxvMoreWords"
                            :key="word"
                            class="detail-kw-tag editable"
                            @click.stop="openWxvMoreKeywordPopup(index)">
                            <text>{{ word }}</text>
                            <view class="rm" @click.stop="removeKeyword(wxvMoreWords, word)">
                                <u-icon name="close" color="#9CA3AF" size="16"></u-icon>
                            </view>
                        </view>
                        <button class="plain-btn detail-kw-more" @click.stop="showWxvMore = false">
                            收起
                            <u-icon name="arrow-up" color="#6B7280" size="18"></u-icon>
                        </button>
                    </view>

                    <view class="lfc-row-between mt-[24rpx]">
                        <text class="text-[24rpx] text-[#6B7280]">每个线索词获客上限</text>
                        <view class="flex items-center gap-[12rpx]">
                            <button class="plain-btn lfc-step-btn" @click.stop="changeWxvLimit(-5)">-</button>
                            <text class="lfc-step-val">{{ wxvLimit }}</text>
                            <button class="plain-btn lfc-step-btn" @click.stop="changeWxvLimit(5)">+</button>
                        </view>
                    </view>
                </template>

                <!-- jl 截流 -->
                <template v-else-if="feature.key === 'jl'">
                    <view class="step-heading">
                        <text class="step-no">1</text>
                        <text class="step-title">视频搜索词</text>
                        <text class="step-desc">用于搜索同行视频</text>
                    </view>
                    <view class="detail-kw-wrap mb-[16rpx]">
                        <view
                            v-for="(word, index) in jlSearchWords"
                            :key="word"
                            class="detail-kw-tag editable"
                            @click.stop="openJlSearchWordPopup(index)">
                            <text>{{ word }}</text>
                            <view class="rm" @click.stop="removeKeyword(jlSearchWords, word)">
                                <u-icon name="close" color="#9CA3AF" size="16"></u-icon>
                            </view>
                        </view>
                        <button class="plain-btn detail-kw-add" @click.stop="openJlSearchWordPopup()">+ 添加</button>
                    </view>
                    <button
                        v-if="jlSearchMoreWords.length && !showJlSearchMore"
                        class="plain-btn detail-kw-more mb-[24rpx]"
                        @click.stop="showJlSearchMore = true">
                        {{ `+${jlSearchMoreWords.length} 个` }}
                        <u-icon name="arrow-down" color="#6B7280" size="18"></u-icon>
                    </button>
                    <view v-if="showJlSearchMore" class="detail-kw-wrap mb-[24rpx]">
                        <view
                            v-for="(word, index) in jlSearchMoreWords"
                            :key="word"
                            class="detail-kw-tag editable"
                            @click.stop="openJlSearchMoreWordPopup(index)">
                            <text>{{ word }}</text>
                            <view class="rm" @click.stop="removeKeyword(jlSearchMoreWords, word)">
                                <u-icon name="close" color="#9CA3AF" size="16"></u-icon>
                            </view>
                        </view>
                        <button class="plain-btn detail-kw-more" @click.stop="showJlSearchMore = false">
                            收起
                            <u-icon name="arrow-up" color="#6B7280" size="18"></u-icon>
                        </button>
                    </view>

                    <view class="lfc-divider"></view>
                    <view class="step-heading">
                        <text class="step-no">2</text>
                        <text class="step-title">评论匹配词</text>
                        <text class="step-desc">筛选意向评论用户</text>
                    </view>
                    <view class="detail-kw-wrap mb-[16rpx]">
                        <view
                            v-for="(word, index) in jlCommentWords"
                            :key="word"
                            class="detail-kw-tag editable"
                            @click.stop="openJlCommentWordPopup(index)">
                            <text>{{ word }}</text>
                            <view class="rm" @click.stop="removeKeyword(jlCommentWords, word)">
                                <u-icon name="close" color="#9CA3AF" size="16"></u-icon>
                            </view>
                        </view>
                        <button class="plain-btn detail-kw-add" @click.stop="openJlCommentWordPopup()">+ 添加</button>
                        <button
                            v-if="jlCommentMoreWords.length && !showJlCommentMore"
                            class="plain-btn detail-kw-more"
                            @click.stop="showJlCommentMore = true">
                            {{ `+${jlCommentMoreWords.length} 个` }}
                            <u-icon name="arrow-down" color="#6B7280" size="18"></u-icon>
                        </button>
                    </view>
                    <view v-if="showJlCommentMore" class="detail-kw-wrap mb-[24rpx]">
                        <view
                            v-for="(word, index) in jlCommentMoreWords"
                            :key="word"
                            class="detail-kw-tag editable"
                            @click.stop="openJlCommentMoreWordPopup(index)">
                            <text>{{ word }}</text>
                            <view class="rm" @click.stop="removeKeyword(jlCommentMoreWords, word)">
                                <u-icon name="close" color="#9CA3AF" size="16"></u-icon>
                            </view>
                        </view>
                        <button class="plain-btn detail-kw-more" @click.stop="showJlCommentMore = false">
                            收起
                            <u-icon name="arrow-up" color="#6B7280" size="18"></u-icon>
                        </button>
                    </view>

                    <view class="lfc-divider"></view>
                    <view class="lfc-row-between">
                        <text class="text-[24rpx] text-[#6B7280]">每个匹配词截流上限</text>
                        <view class="flex items-center gap-[12rpx]">
                            <button class="plain-btn lfc-step-btn" @click.stop="changeJlLimit(-5)">-</button>
                            <text class="lfc-step-val">{{ jlLimit }}</text>
                            <button class="plain-btn lfc-step-btn" @click.stop="changeJlLimit(5)">+</button>
                        </view>
                    </view>
                    <view class="flex items-center gap-[6rpx] mt-[12rpx]">
                        <u-icon name="info-circle" color="#C0C8D8" size="18"></u-icon>
                        <text class="text-[20rpx] text-[#C0C8D8]">输入 0 表示不限制截流数量</text>
                    </view>
                </template>

                <!-- tg 团购 -->
                <template v-else-if="feature.key === 'tg'">
                    <text class="lfc-sec-title">搜索与定位设置</text>
                    <text class="lfc-sec-lbl">输入团购类型</text>
                    <input v-model="tgForm.type" class="cfg-inp mb-[28rpx]" placeholder="如：双人套餐、火锅、美甲..." />

                    <view class="flex items-center justify-between mb-[20rpx]">
                        <text class="lfc-sec-title mb-0">任务目标与评论筛选</text>
                        <view class="cfg-exec">
                            <text>执行</text>
                            <input v-model="tgForm.execNum" type="number" class="cfg-exec-num" />
                            <text>人</text>
                        </view>
                    </view>
                    <text class="lfc-sec-lbl">评论必须包含以下关键词</text>
                    <view class="cfg-inp-row mb-[20rpx]">
                        <input v-model="tgCommentInput" class="cfg-inp" placeholder="如：怎么买、划算吗..." />
                        <button class="plain-btn cfg-add-btn" @click.stop="addTgKeyword">添加</button>
                    </view>
                    <view class="detail-kw-wrap mb-[20rpx]">
                        <view
                            v-for="(word, index) in tgKeywords"
                            :key="word"
                            class="detail-kw-tag editable"
                            @click.stop="openTgKeywordPopup(index)">
                            {{ word }}
                            <view class="rm" @click.stop="removeKeyword(tgKeywords, word)">
                                <u-icon name="close" color="#9CA3AF" size="16"></u-icon>
                            </view>
                        </view>
                    </view>
                    <view class="cfg-num-card mb-[28rpx]">
                        <text class="cfg-num-lbl">从第几个评论开始</text>
                        <input v-model="tgForm.commentStart" type="number" class="cfg-num-input" placeholder="1" />
                    </view>

                    <text class="lfc-sec-title">对评论用户执行动作</text>
                    <action-config
                        :like="tgActions.like"
                        :follow="tgActions.follow"
                        :dual="tgActions.dual"
                        :show-like="false"
                        :show-follow="false"
                        @toggle-like="tgActions.like = !tgActions.like"
                        @toggle-follow="tgActions.follow = !tgActions.follow"
                        @dual="tgActions.dual = $event" />

                    <view class="grid grid-cols-2 gap-[16rpx] mb-[28rpx]">
                        <view class="cfg-num-card">
                            <text class="cfg-num-lbl">观看视频（秒）</text>
                            <input v-model="tgForm.watchSeconds" type="number" class="cfg-num-input" />
                        </view>
                        <view class="cfg-num-card">
                            <text class="cfg-num-lbl">触达间隔（秒）</text>
                            <input v-model="tgForm.touchInterval" type="number" class="cfg-num-input" />
                        </view>
                    </view>

                    <text class="lfc-sec-title">目标画像与地域</text>
                    <text class="lfc-sec-lbl">性别要求</text>
                    <view class="gender-bar mb-[24rpx]">
                        <button
                            v-for="gender in genderOptions"
                            :key="gender"
                            class="plain-btn gender-btn"
                            :class="{ sel: tgForm.gender === gender }"
                            @click.stop="tgForm.gender = gender">
                            {{ gender }}
                        </button>
                    </view>
                    <view class="target-region-grid">
                        <view>
                            <text class="lfc-sec-lbl">筛选 IP</text>
                            <input v-model="tgForm.ip" class="cfg-inp region-input" placeholder="如：浙江" />
                        </view>
                        <view>
                            <text class="lfc-sec-lbl">筛选地区</text>
                            <input v-model="tgForm.region" class="cfg-inp region-input" placeholder="如：杭州" />
                        </view>
                    </view>
                    <exclude-editor
                        v-model:input="tgExcludeInput"
                        :words="tgExcludeWords"
                        placeholder="如：同行、客服..."
                        @add="addTgExcludeWord"
                        @remove="removeKeyword(tgExcludeWords, $event)"
                        @edit="openTgExcludeWordPopup($event.index)" />
                </template>

                <!-- tc 同城 -->
                <template v-else>
                    <text class="lfc-sec-title">互动与触达动作</text>
                    <action-config
                        :like="tcActions.like"
                        :follow="tcActions.follow"
                        :dual="tcActions.dual"
                        :show-like="false"
                        :show-follow="false"
                        @toggle-like="tcActions.like = !tcActions.like"
                        @toggle-follow="tcActions.follow = !tcActions.follow"
                        @dual="tcActions.dual = $event" />
                    <view class="grid grid-cols-2 gap-[16rpx] mb-[28rpx]">
                        <view class="cfg-num-card">
                            <text class="cfg-num-lbl">观看视频（秒）</text>
                            <input v-model="tcForm.watchSeconds" type="number" class="cfg-num-input" />
                        </view>
                        <view class="cfg-num-card">
                            <text class="cfg-num-lbl">触达间隔（秒）</text>
                            <input v-model="tcForm.touchInterval" type="number" class="cfg-num-input" />
                        </view>
                    </view>

                    <view class="flex items-center justify-between mb-[20rpx]">
                        <text class="lfc-sec-title mb-0">评论者画像</text>
                        <button class="plain-btn city-btn">全城</button>
                    </view>
                    <text class="lfc-sec-lbl">附近距离范围</text>
                    <view class="dist-wrap mb-[24rpx]">
                        <view
                            v-for="distance in distanceOptions"
                            :key="distance"
                            class="dist-chip"
                            :class="{ sel: tcForm.distance === distance }"
                            @click.stop="selectDistance(distance)">
                            {{ distance }}
                        </view>
                    </view>
                    <view v-if="tcForm.distance === '自定义'" class="custom-distance-card mb-[24rpx]">
                        <input
                            :value="tcForm.customDistance"
                            type="number"
                            class="custom-distance-input"
                            placeholder="请输入正整数"
                            @input="customDistanceInput" />
                        <text class="custom-distance-unit">km</text>
                    </view>
                    <view class="grid grid-cols-2 gap-[16rpx] mb-[24rpx]">
                        <view>
                            <text class="lfc-sec-lbl">性别要求</text>
                            <view class="gender-bar">
                                <button
                                    v-for="gender in genderOptions"
                                    :key="gender"
                                    class="plain-btn gender-btn"
                                    :class="{ sel: tcForm.gender === gender }"
                                    @click.stop="tcForm.gender = gender">
                                    {{ gender }}
                                </button>
                            </view>
                        </view>
                        <view>
                            <text class="lfc-sec-lbl">年龄范围</text>
                            <view class="range-card">
                                <view class="range-row">
                                    <input v-model="tcForm.ageMin" type="number" class="range-inp" />
                                    <text class="range-sep">-</text>
                                    <input v-model="tcForm.ageMax" type="number" class="range-inp" />
                                </view>
                            </view>
                        </view>
                    </view>

                    <text class="lfc-sec-title">作品与账号过滤</text>
                    <view class="grid grid-cols-2 gap-[16rpx] mb-[20rpx]">
                        <view class="range-card">
                            <text class="cfg-num-lbl">视频作品满足（赞）</text>
                            <view class="range-row">
                                <input v-model="tcForm.likeMin" type="number" class="range-inp" />
                                <text class="range-unit">以上</text>
                            </view>
                        </view>
                        <view class="range-card">
                            <text class="cfg-num-lbl">视频评论数不大于</text>
                            <view class="range-row">
                                <input v-model="tcForm.commentMax" type="number" class="range-inp" />
                                <text class="range-unit">条</text>
                            </view>
                        </view>
                    </view>
                    <view class="grid grid-cols-2 gap-[16rpx] mb-[24rpx]">
                        <view>
                            <text class="lfc-sec-lbl">目标评论粉丝数量</text>
                            <view class="range-card">
                                <view class="range-row">
                                    <input v-model="tcForm.fansMin" type="number" class="range-inp" />
                                    <text class="range-sep">-</text>
                                    <input v-model="tcForm.fansMax" type="number" class="range-inp" />
                                </view>
                            </view>
                        </view>
                        <view>
                            <text class="lfc-sec-lbl">目标评论关注数量</text>
                            <view class="range-card">
                                <view class="range-row">
                                    <input v-model="tcForm.followMin" type="number" class="range-inp" />
                                    <text class="range-sep">-</text>
                                    <input v-model="tcForm.followMax" type="number" class="range-inp" />
                                </view>
                            </view>
                        </view>
                    </view>
                    <exclude-editor
                        v-model:input="tcExcludeInput"
                        :words="tcExcludeWords"
                        placeholder="如：店长、主播..."
                        @add="addTcExcludeWord"
                        @remove="removeKeyword(tcExcludeWords, $event)"
                        @edit="openTcExcludeWordPopup($event.index)" />
                </template>
            </view>
        </view>

        <!-- 防封与风控 -->
        <view class="feat-card risk-card">
            <view class="lfc-hd" @click.stop="showLeadRiskPanel = !showLeadRiskPanel">
                <view class="risk-icon">
                    <u-icon name="setting-fill" color="#2F73F6" size="28"></u-icon>
                </view>
                <view class="flex-1 min-w-0">
                    <text class="block text-[28rpx] font-bold text-[#1F2937]">防封与风控</text>
                    <text class="block text-[24rpx] text-[#9CA3AF] mt-[2rpx] line-clamp-1">
                        控制触达频率，模拟真人互动节奏
                    </text>
                </view>
                <u-icon
                    name="arrow-right"
                    color="#C0C8D8"
                    size="22"
                    :custom-style="{
                        transform: showLeadRiskPanel ? 'rotate(90deg)' : 'rotate(0deg)',
                        transition: 'transform 0.28s cubic-bezier(.4,0,.2,1)',
                    }"></u-icon>
            </view>
            <view v-if="showLeadRiskPanel" class="lfc-inner">
                <view class="risk-note">
                    <text>
                        已开启拟人随机停顿。每次互动后，系统将随机停留 30秒~2分钟，模拟真人浏览行为，降低风控风险。
                    </text>
                </view>

                <text class="lfc-sec-title">触达时间限制</text>
                <text class="lfc-sec-lbl">内容发布日期</text>
                <view class="risk-time-grid mb-[24rpx]">
                    <view
                        v-for="option in contentPublishTimeOptions"
                        :key="'content-' + option.value"
                        class="risk-time-chip"
                        :class="{ active: riskConfig.contentPublishTime === option.value }"
                        @click.stop="riskConfig.contentPublishTime = option.value">
                        {{ option.label }}
                    </view>
                </view>
                <text class="lfc-sec-lbl">评论发布日期</text>
                <view class="risk-time-grid mb-[28rpx]">
                    <view
                        v-for="option in commentPublishTimeOptions"
                        :key="'comment-' + option.value"
                        class="risk-time-chip"
                        :class="{ active: riskConfig.commentPublishTime === option.value }"
                        @click.stop="riskConfig.commentPublishTime = option.value">
                        {{ option.label }}
                    </view>
                </view>

                <view class="lfc-divider"></view>
                <text class="lfc-sec-title">频率限制</text>

                <view v-for="item in riskSliderList" :key="item.key" class="risk-slider-block">
                    <view class="flex items-center justify-between mb-[14rpx]">
                        <text class="risk-slider-label">{{ item.label }}</text>
                        <text class="risk-slider-value">{{ riskConfig[item.key] }}人</text>
                    </view>
                    <slider
                        :value="Number(riskConfig[item.key])"
                        min="0"
                        max="30"
                        step="1"
                        block-size="20"
                        activeColor="#0065fb"
                        backgroundColor="#E5E7EB"
                        @changing="(e:any) => (riskConfig[item.key] = String(e.detail.value))"
                        @change="(e: any) => (riskConfig[item.key] = String(e.detail.value))" />
                    <view class="flex items-center justify-between mt-[4rpx]">
                        <text class="risk-slider-tip">保守 (防封)</text>
                        <text class="risk-slider-tip">激进 (易封)</text>
                    </view>
                </view>

                <view class="risk-reply-card">
                    <view class="flex items-center justify-between mb-[16rpx]">
                        <text class="risk-slider-label">私信每个接管用户回复数</text>
                        <text class="risk-slider-value">
                            {{ riskConfig.replyNumber === 1 ? "1人" : "无限制" }}
                        </text>
                    </view>
                    <view class="risk-reply-options">
                        <button
                            class="plain-btn risk-reply-option"
                            :class="{ active: riskConfig.replyNumber === 1 }"
                            @click.stop="riskConfig.replyNumber = 1">
                            1条
                        </button>
                        <button
                            class="plain-btn risk-reply-option"
                            :class="{ active: riskConfig.replyNumber === 2 }"
                            @click.stop="riskConfig.replyNumber = 2">
                            无限制
                        </button>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import ActionConfig from "../../action-config.vue";
import ExcludeEditor from "../../exclude-editor.vue";
import { LeadFeaturesKey } from "../../../hooks/useLeadFeatures";

// 从父层注入 hook 上下文：state + handlers 全部一站式取出，零 prop drilling
// 业务上 LeadsPanel 仅在 ai-employee-step.vue 内渲染，provide 链确定存在；用 ! 断言避免给用户面前抛 Error
const ctx = inject(LeadFeaturesKey)!;

const {
    // 常量
    leadFeatureList,
    genderOptions,
    distanceOptions,
    contentPublishTimeOptions,
    commentPublishTimeOptions,
    riskSliderList,
    // 状态
    leadFeatureStatus,
    openLeadFeature,
    showLeadRiskPanel,
    wxvKeywords,
    wxvMoreWords,
    showWxvMore,
    wxvLimit,
    jlSearchWords,
    jlSearchMoreWords,
    jlCommentWords,
    jlCommentMoreWords,
    showJlSearchMore,
    showJlCommentMore,
    jlLimit,
    tgForm,
    tgCommentInput,
    tgKeywords,
    tgActions,
    tgExcludeInput,
    tgExcludeWords,
    tcForm,
    tcActions,
    tcExcludeInput,
    tcExcludeWords,
    riskConfig,
    // 操作
    removeKeyword,
    toggleLeadFeature,
    changeWxvLimit,
    changeJlLimit,
    selectDistance,
    customDistanceInput,
    openWxvKeywordPopup,
    openWxvMoreKeywordPopup,
    openJlSearchWordPopup,
    openJlSearchMoreWordPopup,
    openJlCommentWordPopup,
    openJlCommentMoreWordPopup,
    openTgKeywordPopup,
    openTgExcludeWordPopup,
    openTcExcludeWordPopup,
    addTgKeyword,
    addTgExcludeWord,
    addTcExcludeWord,
} = ctx;

const test = ref("1");
</script>

<style scoped lang="scss">
// 与主文件原版严格一致
.plain-btn {
    @apply m-0 p-0 leading-none border-none bg-[transparent];

    &::after {
        border: none;
    }
}

.cfg-inp {
    @apply w-full min-h-[88rpx] bg-[#F9FAFB] border-[3rpx] border-solid border-[transparent] rounded-[24rpx] px-[28rpx] text-[24rpx] text-[#1d2129] box-border;
}

.detail-kw-wrap {
    @apply flex flex-wrap gap-[16rpx];
}

.detail-kw-tag {
    @apply inline-flex items-center gap-[10rpx] text-xs text-[#1d2129] font-medium bg-white border-[3rpx] border-solid border-[#e5e7eb] py-[12rpx] px-[24rpx] rounded-full;

    &.editable {
        @apply pr-[18rpx];
    }

    &.orange {
        @apply bg-[#fff7ed] border-[#fed7aa] text-[#ea580c];
    }

    .rm {
        @apply inline-flex items-center justify-center leading-none;
    }
}

.detail-kw-add,
.detail-kw-more {
    @apply inline-flex items-center gap-[8rpx] text-[24rpx] font-medium py-[12rpx] px-[26rpx] rounded-full;
}

.detail-kw-add {
    @apply text-[#2f73f6] border-[3rpx] border-dashed border-[#93bbfd];

    &.orange {
        @apply text-[#f97316] border-[#fed7aa];
    }
}

.detail-kw-more {
    @apply text-[#6b7280] bg-[#f3f4f6] border-[3rpx] border-solid border-[#e5e7eb];
}

.feat-card {
    @apply bg-white rounded-[28rpx] border-[3rpx] border-solid border-[#e5e7eb] overflow-hidden;

    &.on {
        @apply border-[#e5e7eb] bg-white;
    }
}

.risk-card {
    @apply border-[#dbeafe];
}

.risk-icon {
    @apply w-[72rpx] h-[72rpx] rounded-[24rpx] bg-[#e6f0ff] flex items-center justify-center shrink-0;
}

.risk-note {
    @apply rounded-[20rpx] bg-[#e6f0ff] py-[20rpx] px-[24rpx] text-[22rpx] text-[#2f73f6] leading-relaxed mb-[28rpx];
}

.risk-time-grid {
    @apply grid grid-cols-4 gap-[12rpx];
}

.risk-time-chip {
    @apply min-h-[64rpx] rounded-[18rpx] bg-[#f5f7fb] text-[22rpx] font-semibold text-[#8a94a6] flex items-center justify-center text-center;

    &.active {
        @apply bg-primary text-white;
    }
}

.risk-slider-block {
    @apply mb-[28rpx];
}

.risk-slider-label {
    @apply text-[24rpx] font-bold text-[#1d2129];
}

.risk-slider-value {
    @apply text-[28rpx] font-extrabold text-[#2f73f6] shrink-0;
}

.risk-slider-tip {
    @apply text-[20rpx] text-[#b4b4b4];
}

.risk-reply-card {
    @apply bg-[#f8fafc] rounded-[24rpx] py-[22rpx] px-[24rpx];
}

.risk-reply-options {
    @apply grid grid-cols-2 gap-[12rpx];
}

.risk-reply-option {
    @apply min-h-[64rpx] rounded-[18rpx] bg-white text-[24rpx] font-semibold text-[#9ca3af] flex items-center justify-center border-[2rpx] border-solid border-[#e5e7eb];

    &.active {
        @apply bg-[#ebf2ff] text-[#2f73f6] border-[#bad4ff];
    }
}

.lfc-hd {
    @apply flex items-center gap-[20rpx] py-[26rpx] px-[28rpx];
}

.lfc-inner {
    @apply pt-[24rpx] px-[28rpx] pb-[32rpx] border-0 border-t-[2rpx] border-solid border-[#f0f4fa];
}

.lfc-divider {
    @apply h-[2rpx] bg-[#f0f4fa] my-[24rpx];
}

.lfc-sec-title {
    @apply block text-[24rpx] font-bold text-[#1d2129] border-0 border-l-[5rpx] border-solid border-[#2f73f6] pl-[16rpx] mt-[28rpx] mb-[20rpx];

    &:first-child {
        @apply mt-0;
    }
}

.lfc-sec-lbl {
    @apply block text-[22rpx] font-semibold text-[#9ca3af] mb-[16rpx];
}

.step-heading {
    @apply flex items-center gap-[16rpx] mb-[16rpx];
}

.step-no {
    @apply w-[36rpx] h-[36rpx] rounded-full bg-[#f97316] text-white text-[20rpx] font-bold flex items-center justify-center;
}

.step-title {
    @apply text-[24rpx] font-bold text-[#1d2129];
}

.step-desc {
    @apply text-[20rpx] text-[#9ca3af];
}

.lfc-row-between {
    @apply flex items-center justify-between bg-[#f9fafb] rounded-[20rpx] py-[20rpx] px-[24rpx];
}

.lfc-step-btn {
    @apply w-[52rpx] h-[52rpx] rounded-[16rpx] bg-[#f3f4f6] text-[#6b7280] text-[32rpx] flex items-center justify-center;
}

.lfc-step-val {
    @apply min-w-[52rpx] text-center text-[28rpx] font-bold text-[#1d2129];
}

.cfg-inp-row {
    @apply flex gap-[16rpx] items-stretch;

    .cfg-inp {
        @apply flex-1;
    }
}

.target-region-grid {
    @apply grid grid-cols-2 gap-[20rpx] mb-[28rpx];
}

.region-input {
    @apply min-h-[96rpx] rounded-[26rpx] bg-[#F9FAFB] text-[26rpx];
}

.cfg-add-btn {
    @apply bg-[#2f73f6] text-white text-[26rpx] font-bold rounded-[24rpx] px-[36rpx] min-h-[88rpx] flex items-center justify-center shrink-0;
}

.cfg-exec {
    @apply flex items-center gap-[10rpx] text-[22rpx] text-[#9ca3af];
}

.cfg-exec-num {
    @apply w-[88rpx] h-[56rpx] bg-[#ebf2ff] border-[3rpx] border-solid border-[#bad4ff] rounded-[20rpx] text-[#2f73f6] text-[28rpx] font-extrabold text-center;
}

.cfg-num-card,
.range-card {
    @apply bg-[#f3f7fc] rounded-[28rpx] py-[24rpx] px-[28rpx];
}

.cfg-num-lbl {
    @apply block text-[22rpx] text-[#9ca3af] mb-[12rpx];
}

.cfg-num-val {
    @apply block text-[40rpx] font-extrabold text-[#1d2129] leading-none;
}

.cfg-num-input {
    @apply block w-full p-0 bg-[transparent] text-[40rpx] font-extrabold text-[#1d2129] leading-none;
}

.gender-bar {
    @apply flex bg-[#f3f7fc] rounded-[24rpx] p-[6rpx] gap-[4rpx];
}

.gender-btn {
    @apply flex-1 min-h-[66rpx] rounded-[20rpx] text-[24rpx] font-semibold text-[#9ca3af] flex items-center justify-center;

    &.sel {
        @apply bg-white text-[#1d2129];
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.1);
    }
}

.dist-wrap {
    @apply flex flex-wrap gap-[16rpx];
}

.dist-chip {
    @apply py-[14rpx] px-[28rpx] rounded-full text-[24rpx] font-medium border-[3rpx] border-solid border-[#e5e7eb] bg-[#f3f4f6] text-[#6b7280];

    &.sel {
        @apply bg-[#ebf2ff] border-[#2f73f6] text-[#2f73f6] font-bold;
    }
}

.custom-distance-card {
    @apply min-h-[88rpx] bg-[#f3f7fc] rounded-[24rpx] py-[10rpx] px-[28rpx] flex items-center gap-[12rpx];
}

.custom-distance-input {
    @apply flex-1 min-w-0 h-[68rpx] bg-[transparent] text-[30rpx] font-bold text-[#1d2129];
}

.custom-distance-unit {
    @apply text-[24rpx] font-semibold text-[#9ca3af] shrink-0;
}

.range-row {
    @apply flex items-center gap-[8rpx];
}

.range-inp {
    @apply flex-1 min-w-0 w-0 bg-[transparent] text-[32rpx] font-extrabold text-[#1d2129] text-center;
}

.range-sep {
    @apply text-[#c0c8d8] text-[28rpx];
}

.range-unit {
    @apply text-[#9ca3af] text-[24rpx] shrink-0;
}

.city-btn {
    @apply text-[24rpx] font-semibold text-[#2f73f6] bg-[#ebf2ff] border-[3rpx] border-solid border-[#bad4ff] rounded-[16rpx] py-[8rpx] px-[24rpx];
}
</style>
