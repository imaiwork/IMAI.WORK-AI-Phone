<template>
    <div class="edit-popup">
        <popup
            ref="popupRef"
            title="编辑人设信息"
            :async="true"
            width="800px"
            @confirm="handleSubmit"
            @close="handleClose">
            <div class="py-2 flex flex-col gap-4">
                <!-- ═══════════ 基础信息 ═══════════ -->
                <div class="flex items-center gap-2">
                    <div class="w-1 h-4 rounded-sm bg-gradient-to-b from-[#667eea] to-[#764ba2]" />
                    <span class="text-sm font-bold text-[#1e1e2e]">基础信息</span>
                </div>

                <!-- ① 头像 + 名字 + 简介 -->
                <div class="bg-white rounded-2xl px-5 py-5 border border-[#f1f3f9] flex items-center gap-5">
                    <div class="flex-shrink-0">
                        <material-picker :limit="1" v-model="basicForm.avatar_url" />
                    </div>
                    <div class="flex-1 min-w-0 flex flex-col gap-2">
                        <div class="flex items-center border-b border-[#f0f3f9] pb-1.5">
                            <el-input
                                v-model="basicForm.ai_name"
                                maxlength="30"
                                placeholder="请填写 AI 名字"
                                class="bare-input bare-input--lg" />
                            <el-icon class="text-[#B8C3D6] flex-shrink-0" :size="16"><Edit /></el-icon>
                        </div>
                        <div class="flex items-center">
                            <el-input
                                v-model="basicForm.intro"
                                maxlength="80"
                                placeholder="填写简介（选填）..."
                                class="bare-input bare-input--sm" />
                            <el-icon class="text-[#B8C3D6] flex-shrink-0" :size="16"><Edit /></el-icon>
                        </div>
                    </div>
                </div>

                <!-- ② 账号类型 -->
                <div class="bg-white rounded-2xl px-5 py-4 border border-[#f1f3f9]">
                    <div class="text-xs font-bold text-[#86909C] mb-3">账号类型</div>
                    <div class="flex gap-3">
                        <div
                            v-for="item in personTypeOptions"
                            :key="item.value"
                            class="flex-1 h-10 rounded-2xl border-2 flex items-center justify-center cursor-pointer transition-all select-none"
                            :class="
                                basicForm.persona_type === item.value
                                    ? 'bg-[#EBF3FF] border-primary'
                                    : 'bg-[#F6F8FC] border-[#E5EBF8] hover:border-[#bfdbfe]'
                            "
                            @click="basicForm.persona_type = item.value">
                            <span class="text-sm mr-1.5">{{ item.icon }}</span>
                            <span
                                class="text-xs font-bold whitespace-nowrap"
                                :class="basicForm.persona_type === item.value ? 'text-primary' : 'text-[#86909C]'">
                                {{ item.label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ③ 告诉 AI 你是谁（6 步） -->
                <div class="bg-white rounded-2xl px-5 py-5 border border-[#f1f3f9]">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div
                                class="w-10 h-10 rounded-xl bg-[#EAF2FF] flex items-center justify-center mr-3 flex-shrink-0">
                                <el-icon class="text-primary" :size="18"><Document /></el-icon>
                            </div>
                            <div>
                                <div class="text-base font-black text-[#111827] leading-snug">告诉 AI 你是谁</div>
                                <div class="text-[11px] text-[#7B8798] font-semibold mt-0.5">
                                    填得越准确，生成内容越好用
                                </div>
                            </div>
                        </div>
                        <div
                            class="h-6 px-2.5 rounded-full inline-flex items-center"
                            :class="isInfoComplete ? 'bg-[#E4F8ED]' : 'bg-[#F2F6FC]'">
                            <el-icon :class="isInfoComplete ? 'text-[#10B981]' : 'text-[#9CA3AF]'" :size="13">
                                <CircleCheck v-if="isInfoComplete" />
                                <MoreFilled v-else />
                            </el-icon>
                            <span
                                class="ml-1 text-[11px] font-bold"
                                :class="isInfoComplete ? 'text-[#10B981]' : 'text-[#9CA3AF]'">
                                {{ isInfoComplete ? "已完成" : "待完善" }}
                            </span>
                        </div>
                    </div>

                    <!-- step 1: 行业方向（最多 3 项） -->
                    <div class="pb-5 mb-5 border-b border-[#EDF2F8]">
                        <div class="flex items-start mb-3">
                            <div class="step-no">1</div>
                            <div class="flex-1">
                                <div class="text-sm font-black text-[#111827]">你是做什么的？</div>
                                <div class="text-[11px] text-[#86909C] mt-0.5">选择你的内容方向（最多 3 项）</div>
                            </div>
                        </div>
                        <div
                            class="min-h-[44px] rounded-2xl bg-[#F4F7FC] px-4 py-2.5 flex items-center justify-between cursor-pointer transition-all"
                            :class="
                                industryDirectionText ? 'border-2 border-[#D9E8FF]' : 'border-2 border-[transparent]'
                            "
                            @click="showIndustrySheet = true">
                            <span
                                class="text-xs leading-snug flex-1 line-clamp-2 mr-2"
                                :class="industryDirectionText ? 'text-[#111827]' : 'text-[#B8C3D6]'">
                                {{ industryDirectionText || "点击选择行业方向（可多选）" }}
                            </span>
                            <el-icon class="text-[#B8C3D6]" :size="14"><ArrowRight /></el-icon>
                        </div>
                    </div>

                    <!-- step 2: 内容方向 -->
                    <div class="pb-5 mb-5 border-b border-[#EDF2F8]">
                        <div class="flex items-start mb-3">
                            <div class="step-no">2</div>
                            <div class="flex-1">
                                <div class="text-sm font-black text-[#111827]">你主要分享什么？</div>
                                <div class="text-[11px] text-[#86909C] mt-0.5">写你平时最想发的内容</div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-[#F4F7FC] px-4 py-3">
                            <el-input
                                v-model="basicForm.content_direction"
                                type="textarea"
                                :rows="3"
                                maxlength="300"
                                resize="none"
                                placeholder="示例：分享敏感肌护肤知识、护肤品测评和护肤避坑"
                                class="bare-input bare-input--textarea" />
                        </div>
                    </div>

                    <!-- step 3: 目标受众 -->
                    <div class="pb-5 mb-5 border-b border-[#EDF2F8]">
                        <div class="flex items-start mb-3">
                            <div class="step-no">3</div>
                            <div class="flex-1">
                                <div class="text-sm font-black text-[#111827]">你想给谁看？</div>
                                <div class="text-[11px] text-[#86909C] mt-0.5">写你的主要目标人群</div>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-[#F4F7FC] px-4 py-3">
                            <el-input
                                v-model="basicForm.target_audience"
                                type="textarea"
                                :rows="3"
                                maxlength="300"
                                resize="none"
                                placeholder="示例：18-35岁女生、敏感肌人群、宝妈、上班族"
                                class="bare-input bare-input--textarea" />
                        </div>
                    </div>

                    <!-- step 4: 账号风格 -->
                    <div class="pb-5 mb-5 border-b border-[#EDF2F8]">
                        <div class="flex items-start mb-3">
                            <div class="step-no">4</div>
                            <div class="flex-1">
                                <div class="text-sm font-black text-[#111827]">你希望账号是什么感觉？</div>
                                <div class="text-[11px] text-[#86909C] mt-0.5">
                                    影响 AI 生成文案的语气风格和私信回复话术
                                </div>
                            </div>
                        </div>
                        <div
                            class="h-11 rounded-2xl bg-[#F4F7FC] px-4 flex items-center justify-between cursor-pointer transition-all"
                            :class="
                                basicForm.account_style ? 'border-2 border-[#D9E8FF]' : 'border-2 border-[transparent]'
                            "
                            @click="showStyleSheet = true">
                            <span
                                class="text-xs"
                                :class="basicForm.account_style ? 'text-[#111827]' : 'text-[#B8C3D6]'">
                                {{ basicForm.account_style || "点击选择账号风格" }}
                            </span>
                            <el-icon class="text-[#B8C3D6]" :size="14"><ArrowRight /></el-icon>
                        </div>
                    </div>

                    <!-- step 5: 城市 / 地区（选填） -->
                    <div class="pb-5 mb-5 border-b border-[#EDF2F8]">
                        <div class="flex items-start mb-3">
                            <div class="step-no">5</div>
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <span class="text-sm font-black text-[#111827]">在哪个城市 / 地区？</span>
                                    <span class="ml-auto text-[11px] text-[#9CA3AF]">选填</span>
                                </div>
                                <div class="text-[11px] text-[#86909C] mt-0.5">
                                    如果你做本地内容，建议填写，精准到门牌号
                                </div>
                            </div>
                        </div>
                        <div
                            class="rounded-2xl bg-[#F4F7FC] px-4 py-3 mb-3 flex items-center justify-between cursor-pointer"
                            @click="basicForm.auto_location = !basicForm.auto_location">
                            <div class="pr-5">
                                <div class="text-sm font-black text-[#111827]">短视频自动挂载本地定位</div>
                                <div class="text-[11px] text-[#86909C] mt-0.5">开启后发布时自动附上门店位置</div>
                            </div>
                            <el-switch v-model="basicForm.auto_location" />
                        </div>
                        <div class="rounded-2xl bg-[#F4F7FC] px-4 py-1 flex items-center">
                            <el-icon class="text-[#86909C] flex-shrink-0" :size="14"><Location /></el-icon>
                            <div class="flex-1 ml-2">
                                <el-input
                                    v-model="basicForm.city_position"
                                    maxlength="120"
                                    clearable
                                    placeholder="示例：深圳市南山区科技园 XX 大厦 1F"
                                    class="bare-input bare-input--sm" />
                            </div>
                        </div>
                    </div>

                    <!-- step 6: 行动目标 -->
                    <div>
                        <div class="flex items-start mb-3">
                            <div class="step-no">6</div>
                            <div class="flex-1">
                                <div class="text-sm font-black text-[#111827]">你希望大家看完后做什么？</div>
                                <div class="text-[11px] text-[#86909C] mt-0.5">
                                    决定 AI 在内容结尾如何引导用户完成行动
                                </div>
                            </div>
                        </div>
                        <div
                            class="h-11 rounded-2xl bg-[#F4F7FC] px-4 flex items-center justify-between cursor-pointer transition-all"
                            :class="
                                basicForm.action_goal ? 'border-2 border-[#D9E8FF]' : 'border-2 border-[transparent]'
                            "
                            @click="showGoalSheet = true">
                            <span class="text-xs" :class="basicForm.action_goal ? 'text-[#111827]' : 'text-[#B8C3D6]'">
                                {{ basicForm.action_goal || "点击选择行动目标" }}
                            </span>
                            <el-icon class="text-[#B8C3D6]" :size="14"><ArrowRight /></el-icon>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ 业务描述 ═══════════ -->
                <div class="flex items-center gap-2 mt-2">
                    <div class="w-1 h-4 rounded-sm bg-gradient-to-b from-[#667eea] to-[#764ba2]" />
                    <span class="text-sm font-bold text-[#1e1e2e]">业务描述</span>
                </div>

                <div
                    class="rounded-2xl px-4 py-3 flex items-start gap-3"
                    style="background: linear-gradient(135deg, #ebf3ff, #f0f7ff); border: 1px solid #bad4ff">
                    <div class="w-10 h-10 rounded-xl bg-[#2F73F6] flex items-center justify-center flex-shrink-0">
                        <el-icon class="text-white" :size="18"><InfoFilled /></el-icon>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-bold text-[#1D2129] leading-snug">填得越准确，AI 内容越好用</div>
                        <div class="text-xs text-[#5C7ECC] mt-1 leading-relaxed">
                            用自己的话说就行，不用写得多好，AI 会根据你说的内容帮你生成视频文案和回复话术。
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-[#f3f4f6]">
                    <div v-for="(field, index) in bizFields" :key="field.key">
                        <div class="mb-6">
                            <div class="flex items-center mb-3">
                                <div
                                    class="w-9 h-9 rounded-lg flex items-center justify-center mr-3 flex-shrink-0"
                                    :style="{ background: field.iconBg }">
                                    <el-icon :style="{ color: field.iconColor }" :size="18">
                                        <component :is="field.icon" />
                                    </el-icon>
                                </div>
                                <span class="text-sm font-semibold text-[#1D2129] flex-1">
                                    {{ field.title }}
                                </span>
                                <span
                                    class="ml-2 px-2 h-5 rounded leading-5 text-[11px] font-semibold text-[#FF4D4F] bg-[#FFF1F0] border border-[#FFCCC7]">
                                    必填
                                </span>
                                <span
                                    class="ml-2 px-3 h-6 rounded-full leading-6 text-[11px] text-primary bg-[#EBF3FF] cursor-pointer select-none"
                                    @click="toggleHint(field.key)">
                                    {{ openHints[field.key] ? "收起示例" : "查看示例" }}
                                </span>
                            </div>

                            <div v-if="openHints[field.key]" class="mb-3 px-3 py-2 rounded-xl bg-[#FFFBEB]">
                                <div
                                    v-for="line in field.example"
                                    :key="line"
                                    class="text-xs text-[#92400E] leading-relaxed">
                                    {{ line }}
                                </div>
                            </div>

                            <el-input
                                v-model="bizForm[field.modelKey]"
                                type="textarea"
                                :rows="4"
                                maxlength="1000"
                                :placeholder="field.placeholder"
                                resize="none"
                                class="biz-textarea" />
                            <div class="flex justify-end mt-1">
                                <span class="text-[11px] text-[#C0C8D8]">
                                    {{ (bizForm[field.modelKey] || "").length }} 字
                                </span>
                            </div>
                        </div>

                        <div v-if="index < bizFields.length - 1" class="h-px bg-[#F3F7FC] mb-6"></div>
                    </div>
                </div>
            </div>
        </popup>

        <!-- 行业方向选择（多选 ≤ 3） -->
        <el-dialog
            v-model="showIndustrySheet"
            title="选择你的行业方向（最多 3 项）"
            width="640px"
            append-to-body
            @open="openIndustryPicker"
            :show-close="true">
            <div class="grid grid-cols-3 gap-2.5 mb-4">
                <div
                    v-for="opt in industryDirectionOptions"
                    :key="opt.name"
                    class="rounded-xl border-2 px-3 py-2.5 cursor-pointer transition-all select-none flex items-center gap-2"
                    :class="
                        industryDraft.includes(opt.name)
                            ? 'border-primary bg-[#EBF3FF] text-primary'
                            : 'border-[#E5EBF8] bg-[#F6F8FC] text-[#4b5563] hover:border-[#bfdbfe]'
                    "
                    @click="toggleIndustryDraft(opt.name)">
                    <span class="text-base">{{ opt.emoji }}</span>
                    <span class="text-sm font-medium">{{ opt.name }}</span>
                </div>
            </div>
            <div class="rounded-xl bg-[#F4F7FC] px-4 py-3">
                <div class="text-xs text-[#86909C] mb-2">没找到？手动填写（最多 10 字）</div>
                <div class="flex items-center gap-2">
                    <el-input
                        v-model="industryCustomInput"
                        placeholder="例：摄影工作室"
                        maxlength="10"
                        show-word-limit
                        size="default" />
                    <el-button type="primary" @click="handleAddCustomIndustry">添加</el-button>
                </div>
                <!-- 自定义（非预设）已选项回显，可删除 -->
                <div v-if="customIndustryDraft.length" class="flex flex-wrap gap-2 mt-3">
                    <el-tag
                        v-for="name in customIndustryDraft"
                        :key="name"
                        closable
                        type="primary"
                        effect="light"
                        round
                        @close="toggleIndustryDraft(name)">
                        {{ name }}
                    </el-tag>
                </div>
            </div>
            <template #footer>
                <span class="text-xs text-[#9CA3AF] mr-auto">已选 {{ industryDraft.length }} / 3</span>
                <el-button @click="showIndustrySheet = false">取消</el-button>
                <el-button type="primary" @click="handleConfirmIndustry">确认</el-button>
            </template>
        </el-dialog>

        <!-- 账号风格选择 -->
        <el-dialog
            v-model="showStyleSheet"
            title="你希望账号是什么感觉？"
            width="600px"
            append-to-body
            @open="openStylePicker">
            <div class="flex flex-col gap-2.5 mb-4">
                <div
                    v-for="opt in accountStyleOptions"
                    :key="opt.name"
                    class="rounded-xl border-2 px-4 py-3 cursor-pointer transition-all select-none"
                    :class="
                        styleDraft === opt.name
                            ? 'border-primary bg-[#EBF3FF]'
                            : 'border-[#E5EBF8] bg-[#F6F8FC] hover:border-[#bfdbfe]'
                    "
                    @click="styleDraft = opt.name">
                    <div class="flex items-center gap-2">
                        <span class="text-base">{{ opt.emoji }}</span>
                        <span
                            class="text-sm font-bold"
                            :class="styleDraft === opt.name ? 'text-primary' : 'text-[#111827]'">
                            {{ opt.name }}
                        </span>
                    </div>
                    <div class="text-xs text-[#86909C] mt-1">{{ opt.desc }}</div>
                </div>
            </div>
            <div class="rounded-xl bg-[#F4F7FC] px-4 py-3">
                <div class="text-xs text-[#86909C] mb-2">自己描述风格，AI 按你要求生成文案和私信回复话术</div>
                <el-input
                    v-model="styleCustomInput"
                    placeholder="例：像老朋友一样，接地气但又有干货…"
                    maxlength="80"
                    show-word-limit />
            </div>
            <template #footer>
                <el-button @click="showStyleSheet = false">取消</el-button>
                <el-button type="primary" @click="handleConfirmStyle">确认</el-button>
            </template>
        </el-dialog>

        <!-- 行动目标选择 -->
        <el-dialog
            v-model="showGoalSheet"
            title="你希望大家看完后做什么？"
            width="600px"
            append-to-body
            @open="openGoalPicker">
            <div class="flex flex-col gap-2.5">
                <div
                    v-for="opt in actionGoalOptions"
                    :key="opt.name"
                    class="rounded-xl border-2 px-4 py-3 cursor-pointer transition-all select-none"
                    :class="
                        goalDraft === opt.name
                            ? 'border-primary bg-[#EBF3FF]'
                            : 'border-[#E5EBF8] bg-[#F6F8FC] hover:border-[#bfdbfe]'
                    "
                    @click="goalDraft = opt.name">
                    <div class="flex items-center gap-2">
                        <span class="text-base">{{ opt.emoji }}</span>
                        <span
                            class="text-sm font-bold"
                            :class="goalDraft === opt.name ? 'text-primary' : 'text-[#111827]'">
                            {{ opt.name }}
                        </span>
                    </div>
                    <div class="text-xs text-[#86909C] mt-1">{{ opt.desc }}</div>
                </div>
            </div>
            <template #footer>
                <el-button @click="showGoalSheet = false">取消</el-button>
                <el-button type="primary" @click="handleConfirmGoal">确认</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script lang="ts" setup>
import { computed, reactive, ref, shallowRef } from "vue";
import {
    Edit,
    ArrowRight,
    Document,
    CircleCheck,
    MoreFilled,
    Location,
    InfoFilled,
    Present,
    UserFilled,
    Promotion,
} from "@element-plus/icons-vue";
import { ElMessageBox } from "element-plus";
import {
    editPersona,
    generatePersonAnalysisReport,
    getPersonClueWords,
    getPersonInteractionWords,
    getPersonTrackingWords,
} from "@/api/ai_application/device/person";
import Popup from "@/components/popup/index.vue";
import feedback from "@/utils/feedback";

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();

enum PersonTypeEnum {
    PERSONAL_IP = 1,
    BUSINESS_SERVICE = 2,
    LOCAL_BUSINESS = 3,
}

// ─── 基础信息选项（与 uniapp basic-info-step 完全对齐）──────────
const personTypeOptions = [
    { label: "个人 IP", icon: "👤", value: PersonTypeEnum.PERSONAL_IP },
    { label: "本地商家", icon: "🏪", value: PersonTypeEnum.LOCAL_BUSINESS },
    { label: "企业服务", icon: "🏢", value: PersonTypeEnum.BUSINESS_SERVICE },
];

const industryDirectionOptions = [
    { name: "美容护肤", emoji: "💄" },
    { name: "探店", emoji: "🍜" },
    { name: "房产", emoji: "🏠" },
    { name: "健身", emoji: "💪" },
    { name: "育儿", emoji: "👶" },
    { name: "宠物", emoji: "🐾" },
    { name: "餐饮美食", emoji: "🍳" },
    { name: "旅游出行", emoji: "✈️" },
    { name: "时尚穿搭", emoji: "👗" },
    { name: "数码科技", emoji: "📱" },
    { name: "教育培训", emoji: "📚" },
    { name: "电商带货", emoji: "📦" },
    { name: "个人成长", emoji: "🌱" },
    { name: "家居装修", emoji: "🛋️" },
    { name: "本地服务", emoji: "📍" },
    { name: "企业品牌", emoji: "💼" },
    { name: "医疗健康", emoji: "🏥" },
    { name: "二手闲置", emoji: "♻️" },
];

const accountStyleOptions = [
    { name: "温柔亲切", emoji: "🌸", desc: "文案温柔走心，私信回复像闺蜜，拉近距离让用户愿意主动聊" },
    { name: "专业可靠", emoji: "🎓", desc: "文案有逻辑有数据，私信回复专业解答，提升信任感和成单率" },
    { name: "活泼开朗", emoji: "🌈", desc: "文案轻快有活力，私信回复热情积极，容易吸引年轻用户互动" },
    { name: "幽默风趣", emoji: "😄", desc: "文案有梗有段子，私信回复轻松好玩，完播率高、评论区容易火" },
    { name: "接地气", emoji: "🤝", desc: "文案说大白话，私信回复直来直去，真实感强、共鸣多易转发" },
    { name: "知性优雅", emoji: "✨", desc: "文案有品位有格调，私信回复得体高雅，适合高端定位和品牌合作" },
];

const actionGoalOptions = [
    { name: "关注我", emoji: "👥", desc: "结尾引导「点关注，下期不迷路」，持续积累账号粉丝" },
    { name: "咨询我", emoji: "💬", desc: "结尾引导「有问题私信我」，把观众变成精准客户线索" },
    { name: "买产品", emoji: "🛍️", desc: "结尾引导「戳购物车」或「评论区有链接」，直接促成购买" },
    { name: "到店", emoji: "📍", desc: "结尾引导「附近的朋友来店里找我」，吸引用户线下到访" },
    { name: "先涨粉", emoji: "🚀", desc: "结尾引导「点赞收藏转发」，快速扩大视频传播和曝光" },
    { name: "品牌曝光", emoji: "🎯", desc: "结尾引导「了解品牌故事」，提升品牌认知和影响力" },
];

// ─── basicForm 与 uniapp BasicFormData 同构 ───
interface BasicForm {
    avatar_url: string;
    ai_name: string;
    intro: string;
    persona_type: PersonTypeEnum;
    industry_direction: string[];
    content_direction: string;
    target_audience: string;
    account_style: string;
    city_position: string;
    auto_location: boolean;
    action_goal: string;
}

const basicForm = reactive<BasicForm>({
    avatar_url: "",
    ai_name: "",
    intro: "",
    persona_type: PersonTypeEnum.PERSONAL_IP,
    industry_direction: [],
    content_direction: "",
    target_audience: "",
    account_style: "",
    city_position: "",
    auto_location: true,
    action_goal: "",
});

// ─── 业务描述：三项统一走顶层字段（对齐 uniapp business-desc-step）───
const bizForm = reactive<Record<string, string>>({
    main_business: "",
    target_pain_points: "",
    conversion_hook: "",
});

interface BizField {
    key: string;
    modelKey: string;
    icon: any;
    iconColor: string;
    iconBg: string;
    title: string;
    placeholder: string;
    example: string[];
}

const bizFields: BizField[] = [
    {
        key: "product",
        modelKey: "main_business",
        icon: Present,
        iconColor: "#F97316",
        iconBg: "#FFF3E6",
        title: "你在做什么 / 卖什么？",
        placeholder: "例：我在做宠物洗护，也卖生骨肉猫粮；或：我拍敏感肌护肤测评视频",
        example: ['💡 例："猫咪洗护 · 生骨肉定制 · 宠物寄养"', '"敏感肌护肤推荐 · 成分科普 · 爆款好物种草"'],
    },
    {
        key: "audience",
        modelKey: "target_pain_points",
        icon: UserFilled,
        iconColor: "#2F73F6",
        iconBg: "#EBF3FF",
        title: "你想卖给谁 / 给谁看？",
        placeholder: "例：深圳养猫的宝妈，30岁左右，担心猫咪吃不好；或：有敏感肌的上班族女生",
        example: ['💡 例："深圳铲屎官 · 养猫宝妈 · 注重健康的年轻人"', '"18-35岁都市女性，有敏感肌困扰，注重护肤品质"'],
    },
    {
        key: "advantage",
        modelKey: "conversion_hook",
        icon: Promotion,
        iconColor: "#7C3AED",
        iconBg: "#F0EEFF",
        title: "你比别人好在哪？",
        placeholder: "例：食材零添加、洗护不哭闹；或：产品全部亲测，不接广告，真实推荐",
        example: ['💡 例："零添加食材 · 温柔洗护无哭声"', '"所有产品亲测不踩雷，拒绝广告硬植入，干货丰富"'],
    },
];

const openHints = reactive<Record<string, boolean>>({});
const toggleHint = (key: string): void => {
    openHints[key] = !openHints[key];
};

// 顶层元信息（提交时透传，无独立 UI）
const meta = reactive({
    id: "" as string | number,
    is_shopping_cart: 0 as 0 | 1,
    goods_name: "",
});
const originPersonaType = ref<number>(0);

const industryDirectionText = computed(() => basicForm.industry_direction.join("、"));

// 已选但不在预设网格中的自定义项（需在选择器中单独回显，否则用户看不到/无法删除）
const customIndustryDraft = computed(() =>
    industryDraft.value.filter((name) => !industryDirectionOptions.some((opt) => opt.name === name)),
);

const isInfoComplete = computed(
    () =>
        Boolean(basicForm.persona_type) &&
        basicForm.industry_direction.length > 0 &&
        Boolean(basicForm.account_style) &&
        Boolean(basicForm.action_goal),
);

// ─── 弹窗选择器（草稿态：取消还原） ───
const showIndustrySheet = ref(false);
const showStyleSheet = ref(false);
const showGoalSheet = ref(false);
const industryDraft = ref<string[]>([]);
const industryCustomInput = ref("");
const styleDraft = ref("");
const styleCustomInput = ref("");
const goalDraft = ref("");

const openIndustryPicker = () => {
    industryDraft.value = [...basicForm.industry_direction];
    industryCustomInput.value = "";
};
const openStylePicker = () => {
    const matchedPreset = accountStyleOptions.some((o) => o.name === basicForm.account_style);
    styleDraft.value = matchedPreset ? basicForm.account_style : "";
    styleCustomInput.value = matchedPreset ? "" : basicForm.account_style;
};
const openGoalPicker = () => {
    goalDraft.value = basicForm.action_goal;
};

const toggleIndustryDraft = (name: string) => {
    const i = industryDraft.value.indexOf(name);
    if (i > -1) {
        industryDraft.value.splice(i, 1);
        return;
    }
    if (industryDraft.value.length >= 3) {
        feedback.msgWarning("最多选择 3 项");
        return;
    }
    industryDraft.value.push(name);
};

const handleAddCustomIndustry = () => {
    const v = industryCustomInput.value.trim();
    if (!v) return;
    if (industryDraft.value.includes(v)) {
        feedback.msgWarning("已存在");
        return;
    }
    if (industryDraft.value.length >= 3) {
        feedback.msgWarning("最多选择 3 项");
        return;
    }
    industryDraft.value.push(v);
    industryCustomInput.value = "";
};

const handleConfirmIndustry = () => {
    if (!industryDraft.value.length) {
        feedback.msgWarning("请至少选择一项");
        return;
    }
    basicForm.industry_direction = [...industryDraft.value];
    showIndustrySheet.value = false;
};

const handleConfirmStyle = () => {
    const finalVal = styleCustomInput.value.trim() || styleDraft.value;
    if (!finalVal) {
        feedback.msgWarning("请选择或填写一个风格");
        return;
    }
    basicForm.account_style = finalVal;
    showStyleSheet.value = false;
};

const handleConfirmGoal = () => {
    if (!goalDraft.value) {
        feedback.msgWarning("请选择一个目标");
        return;
    }
    basicForm.action_goal = goalDraft.value;
    showGoalSheet.value = false;
};

// ─── 初始化（沿用 uniapp init 派生规则）───
const PERSON_TYPE_NAME_MAP: Record<PersonTypeEnum, string> = {
    [PersonTypeEnum.PERSONAL_IP]: "个人IP",
    [PersonTypeEnum.BUSINESS_SERVICE]: "企业服务",
    [PersonTypeEnum.LOCAL_BUSINESS]: "本地商家",
};

const PERSON_DETAIL_FORM_KEY: Record<PersonTypeEnum, "individual" | "enterprise" | "local"> = {
    [PersonTypeEnum.PERSONAL_IP]: "individual",
    [PersonTypeEnum.BUSINESS_SERVICE]: "enterprise",
    [PersonTypeEnum.LOCAL_BUSINESS]: "local",
};

const REPORT_MODEL_MAP: Record<PersonTypeEnum, number> = {
    [PersonTypeEnum.PERSONAL_IP]: 4,
    [PersonTypeEnum.BUSINESS_SERVICE]: 5,
    [PersonTypeEnum.LOCAL_BUSINESS]: 6,
};

// identity / spokesperson 等均为一维字符串数组，做空值过滤归一化
const normalizeStringArray = (raw: unknown): string[] =>
    Array.isArray(raw) ? raw.map((item) => String(item ?? "").trim()).filter(Boolean) : [];

// 报告内容签名：用于判断「业务描述/基础信息」是否变更，决定是否重新生成运营报告
const originReportSignature = ref<string>("");

const hydrate = (detail: Record<string, any>) => {
    const type = (Number(detail.persona_type) || PersonTypeEnum.PERSONAL_IP) as PersonTypeEnum;
    const subKey = PERSON_DETAIL_FORM_KEY[type];
    const sub = (detail[subKey] || {}) as Record<string, any>;

    meta.id = detail.id ?? "";
    meta.is_shopping_cart = Number(detail.is_shopping_cart) === 1 ? 1 : 0;
    meta.goods_name = String(detail.goods_name ?? "");
    originPersonaType.value = Number(detail.persona_type) || 0;

    basicForm.avatar_url = String(detail.avatar_url ?? "");
    basicForm.ai_name = String(detail.persona_name ?? "");
    basicForm.intro = String(detail.persona_desc ?? "");
    basicForm.persona_type = type;

    // 「你是做什么的」回填：个人读 identity，企业/本地读 spokesperson（同槽位，均为一维数组），否则用类型名兜底
    const identityRaw = type === PersonTypeEnum.PERSONAL_IP ? sub.identity : sub.spokesperson;
    const editIdentity = normalizeStringArray(identityRaw);
    if (editIdentity.length) {
        basicForm.industry_direction = editIdentity;
    } else {
        const seed = PERSON_TYPE_NAME_MAP[type] || "";
        basicForm.industry_direction = seed ? [seed] : [];
    }

    basicForm.content_direction =
        String(detail.content_focus ?? "") || String(sub.core_value ?? sub.main_product ?? sub.signature_feature ?? "");
    basicForm.target_audience =
        String(detail.target_audience ?? "") || String(sub.target_audience ?? sub.target_customer ?? "");
    basicForm.account_style =
        normalizeStringArray(sub.personality_tags)[0] ||
        normalizeStringArray(sub.brand_tone)[0] ||
        normalizeStringArray(sub.store_atmosphere)[0] ||
        String(detail.account_style ?? "") ||
        "";
    basicForm.action_goal =
        normalizeStringArray(sub.monetize_paths)[0] ||
        normalizeStringArray(sub.account_goal)[0] ||
        normalizeStringArray(sub.content_preference)[0] ||
        "";

    // 商家定位：顶层 is_store_position / store_position 回填到定位区
    basicForm.auto_location = Number(detail.is_store_position) === 1;
    basicForm.city_position = String(detail.store_position ?? "");

    // 业务描述（顶层）回填，缺失时从子表单兜底
    bizForm.main_business =
        String(detail.main_business ?? "") ||
        String(sub.core_value ?? sub.main_product ?? sub.signature_feature ?? "");
    bizForm.target_pain_points =
        String(detail.target_pain_points ?? "") || String(sub.target_audience ?? sub.target_customer ?? "");
    bizForm.conversion_hook =
        String(detail.conversion_hook ?? "") ||
        String(sub.highlight_story ?? sub.industry_case ?? sub.open_story ?? "");

    originReportSignature.value = buildReportSignature();
};

// ─── basicForm → sub-form（uniapp syncBasicInfoToPayloadFields 端口）───
const personaName = () => {
    const primary = basicForm.industry_direction[0] || "";
    return basicForm.ai_name.trim() || `${primary || "AI"}助手`;
};

const buildSubForm = () => {
    const name = personaName();
    const style = basicForm.account_style.trim();
    const goal = basicForm.action_goal.trim();
    const directions = [...basicForm.industry_direction];
    // 子对象的「分享什么 / 分享给谁」均来自基础信息步骤，业务描述三项独立走顶层字段
    const shareTopic = basicForm.content_direction.trim();
    const shareAudience = basicForm.target_audience.trim();

    if (basicForm.persona_type === PersonTypeEnum.PERSONAL_IP) {
        return {
            nickname: name,
            identity: directions,
            personality_tags: style ? [style] : [],
            core_value: shareTopic,
            target_audience: shareAudience,
            monetize_paths: goal ? [goal] : [],
        };
    }
    if (basicForm.persona_type === PersonTypeEnum.BUSINESS_SERVICE) {
        return {
            brand_name: name,
            spokesperson: directions,
            brand_tone: style ? [style] : [],
            main_product: shareTopic,
            target_customer: shareAudience,
            account_goal: goal ? [goal] : [],
        };
    }
    return {
        store_name: name,
        spokesperson: directions,
        store_atmosphere: style ? [style] : [],
        signature_feature: shareTopic,
        target_customer: shareAudience,
        content_preference: goal ? [goal] : [],
    };
};

const buildPayload = (needGenerateReport: boolean) => {
    const subKey = PERSON_DETAIL_FORM_KEY[basicForm.persona_type];
    return {
        id: meta.id,
        persona_name: personaName(),
        persona_desc: basicForm.intro.trim(),
        persona_type: basicForm.persona_type,
        avatar_url: basicForm.avatar_url,
        // 业务描述：顶层提交
        main_business: bizForm.main_business.trim(),
        target_pain_points: bizForm.target_pain_points.trim(),
        conversion_hook: bizForm.conversion_hook.trim(),
        // 平台购物车（无 UI，透传详情值）
        is_shopping_cart: meta.is_shopping_cart,
        goods_name: meta.is_shopping_cart === 1 ? meta.goods_name.trim() : "",
        // 商家定位：顶层提交（定位 UI 在基础信息步骤）
        is_store_position: basicForm.auto_location ? 1 : 0,
        store_position: basicForm.city_position.trim(),
        [subKey]: buildSubForm(),
        is_create_report: needGenerateReport ? 1 : 0,
    };
};

// 报告内容：业务描述三项写入对应报告槽位（对齐 uniapp buildReportContents）
const buildReportContents = () => {
    const sub = buildSubForm() as Record<string, any>;
    if (basicForm.persona_type === PersonTypeEnum.PERSONAL_IP) {
        return {
            ai_persona_individual: {
                nickname: sub.nickname,
                identity: sub.identity,
                personality_tags: sub.personality_tags,
                core_value: bizForm.main_business.trim(),
                highlight_story: bizForm.conversion_hook.trim(),
                target_audience: bizForm.target_pain_points.trim(),
                monetize_paths: sub.monetize_paths,
            },
        };
    }
    if (basicForm.persona_type === PersonTypeEnum.BUSINESS_SERVICE) {
        return {
            ai_persona_enterprise: {
                brand_name: sub.brand_name,
                spokesperson: sub.spokesperson,
                brand_tone: sub.brand_tone,
                main_product: bizForm.main_business.trim(),
                industry_case: bizForm.conversion_hook.trim(),
                target_customer: bizForm.target_pain_points.trim(),
            },
        };
    }
    return {
        ai_persona_local: {
            store_name: sub.store_name,
            spokesperson: sub.spokesperson,
            store_atmosphere: sub.store_atmosphere,
            signature_feature: bizForm.main_business.trim(),
            open_story: bizForm.conversion_hook.trim(),
            target_customer: bizForm.target_pain_points.trim(),
            content_preference: sub.content_preference,
        },
    };
};

const buildReportSignature = () => JSON.stringify({ type: basicForm.persona_type, ...buildReportContents() });

const hasReportChanged = () => buildReportSignature() !== originReportSignature.value;

const validate = (): boolean => {
    if (!basicForm.avatar_url) {
        feedback.msgWarning("请上传头像");
        return false;
    }
    if (!basicForm.ai_name.trim()) {
        feedback.msgWarning("请填写 AI 名字");
        return false;
    }
    if (!basicForm.persona_type) {
        feedback.msgWarning("请选择账号类型");
        return false;
    }
    if (!basicForm.industry_direction.length) {
        feedback.msgWarning("请选择行业方向（最多 3 项）");
        return false;
    }
    if (!basicForm.account_style) {
        feedback.msgWarning("请选择账号风格");
        return false;
    }
    if (!basicForm.action_goal) {
        feedback.msgWarning("请选择行动目标");
        return false;
    }
    for (const field of bizFields) {
        if (!String(bizForm[field.modelKey] || "").trim()) {
            feedback.msgWarning(`请完善「${field.title}」`);
            return false;
        }
    }
    return true;
};

const confirmTypeChangeIfNeeded = async (): Promise<boolean> => {
    if (!originPersonaType.value || originPersonaType.value === basicForm.persona_type) return true;
    try {
        await feedback.confirm(
            "更换账号类型会让「业务描述」子表单需要重新完善，原内容仍会保留但默认不再展示。是否继续？",
            "更换账号类型",
            { confirmButtonText: "继续保存", cancelButtonText: "取消" },
        );
        return true;
    } catch {
        return false;
    }
};

// 内容总长度过短时的二次确认（沿用旧逻辑）
const checkContentLength = (): Promise<boolean> => {
    const MIN_TOTAL_LENGTH = 300;
    const total = bizFields.reduce((sum, f) => sum + String(bizForm[f.modelKey] || "").trim().length, 0);
    if (total >= MIN_TOTAL_LENGTH) return Promise.resolve(true);
    return new Promise((resolve) => {
        ElMessageBox.confirm("当前内容过少，如果生成报告准确性将不确定，是否继续生成？", "内容过少", {
            confirmButtonText: "确定并生成",
            cancelButtonText: "继续修改",
        })
            .then(() => resolve(true))
            .catch(() => resolve(false));
    });
};

const handleSubmit = async () => {
    if (!validate()) return;
    const okType = await confirmTypeChangeIfNeeded();
    if (!okType) return;

    const needGenerateReport = hasReportChanged();
    if (needGenerateReport) {
        const okLen = await checkContentLength();
        if (!okLen) return;
    }

    const res: any = await editPersona(buildPayload(needGenerateReport));
    const personaId = res?.persona_id || meta.id;

    if (needGenerateReport) {
        // 报告 + 线索话术 / 私信互动话术 / 爆款追踪词 一并刷新（对齐 uniapp handleSubmit）
        generatePersonAnalysisReport({
            persona_id: personaId,
            model: REPORT_MODEL_MAP[basicForm.persona_type],
            contents: buildReportContents(),
        });
        getPersonClueWords({ id: personaId });
        getPersonInteractionWords({ id: personaId });
        getPersonTrackingWords({ id: personaId });
    }

    popupRef.value?.close();
    emit("success");
};

const handleClose = () => emit("close");
const open = () => popupRef.value?.open();

defineExpose({
    open,
    setFormData: (data: Record<string, any>) => hydrate(data || {}),
});
</script>

<style scoped>
.step-no {
    @apply w-7 h-7 rounded-full bg-primary flex items-center justify-center mr-3 flex-shrink-0;
    color: #ffffff;
    font-weight: 700;
    font-size: 12px;
}

/* 内联无边框 input，模拟 uniapp inline 编辑感 */
:deep(.bare-input .el-input__wrapper),
:deep(.bare-input .el-textarea__inner) {
    background: transparent;
    box-shadow: none !important;
    padding: 0;
}
:deep(.bare-input .el-textarea__inner) {
    border: none;
    resize: none;
    font-size: 13px;
    line-height: 1.65;
    color: #111827;
    padding: 0;
}
:deep(.bare-input--lg .el-input__inner) {
    font-size: 16px;
    font-weight: 900;
    color: #111827;
}
:deep(.bare-input--lg .el-input__inner::placeholder) {
    color: #111827;
    font-weight: 900;
}
:deep(.bare-input--sm .el-input__inner) {
    font-size: 13px;
    color: #7b8798;
}
:deep(.bare-input--sm .el-input__inner::placeholder) {
    color: #b8c3d6;
}
:deep(.bare-input--textarea .el-textarea__inner::placeholder) {
    color: #b8c3d6;
}

/* 业务描述 textarea 风格（对齐旧 type-pop） */
:deep(.biz-textarea .el-textarea__inner) {
    border-radius: 16px;
    background: #f3f7fc;
    border: none;
    padding: 16px 20px;
    font-size: 13px;
    line-height: 1.65;
    color: #1d2129;
}
:deep(.biz-textarea .el-textarea__inner::placeholder) {
    color: #c2c9d1;
}
</style>
