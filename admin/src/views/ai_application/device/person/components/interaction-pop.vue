<template>
    <popup
        ref="popupRef"
        title="私域互动管家配置"
        :async="true"
        width="780px"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div class="py-2" v-loading="loading">
            <!-- ───────────── 加好友设置 ───────────── -->
            <field-block
                icon="💬"
                icon-bg="#E6F8F3"
                title="加好友设置"
                tip="自动提取线索发起好友申请时使用的验证话术。">
                <template #extra>
                    <el-switch v-model="privateSwitch.add" />
                </template>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs text-[#b4b4b4]">客资线索库待添加人数</span>
                    <span class="text-xs font-bold text-primary">{{ formData.clue_count }} 人</span>
                </div>
                <rich-textarea
                    v-model="formData.add_friend_script"
                    :max-length="200"
                    placeholder="请输入好友验证申请话术..."
                    :examples="['你好，我是XX品牌顾问，想和您交流一下~', '您好，看到您对我们产品感兴趣，特来添加']" />
            </field-block>

            <!-- ───────────── 自动加群设置 ───────────── -->
            <field-block
                icon="👥"
                icon-bg="linear-gradient(90deg,#43e97b 0%,#38f9d7 100%)"
                title="自动加群设置"
                tip="在执行微信平台任务时将对新好友进行拉群操作。">
                <template #extra>
                    <el-switch v-model="privateSwitch.group" />
                </template>

                <template v-if="privateSwitch.group">
                    <div class="border-t border-[#F3F4F6] pt-4 space-y-4">
                        <!-- 加群触发模式 -->
                        <div>
                            <span class="text-xs font-bold text-[#212121]">加群触发模式</span>
                            <div class="inline-flex bg-[#F3F4F6] rounded-lg p-1 gap-1 mt-2">
                                <button
                                    type="button"
                                    class="px-4 py-1.5 rounded-md text-xs font-bold transition-all"
                                    :class="
                                        formData.group_trigger_mode === 1
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-[#9CA3AF]'
                                    "
                                    @click="formData.group_trigger_mode = 1">
                                    AI 意图识别
                                </button>
                                <button
                                    type="button"
                                    class="px-4 py-1.5 rounded-md text-xs font-bold transition-all"
                                    :class="
                                        formData.group_trigger_mode === 2
                                            ? 'bg-white text-primary shadow-sm'
                                            : 'text-[#9CA3AF]'
                                    "
                                    @click="formData.group_trigger_mode = 2">
                                    自定义触发词
                                </button>
                            </div>
                            <div
                                v-if="formData.group_trigger_mode === 1"
                                class="bg-[#EFF6FF] border border-[#BFDBFE] rounded-lg px-3 py-2 mt-2 flex items-start gap-2">
                                <el-icon color="#2563EB" class="mt-0.5 flex-shrink-0"><InfoFilled /></el-icon>
                                <p class="text-xs text-[#1E40AF] leading-relaxed m-0">
                                    AI 自动识别客户对话中的拉群意图，无需关键词配置。
                                </p>
                            </div>
                            <template v-else>
                                <div
                                    v-if="formData.group_trigger_keywords.length"
                                    class="flex items-center justify-end mt-2">
                                    <el-button type="danger" link @click="handleClearAllGroupTriggerKeywords">
                                        <el-icon class="mr-0.5"><Delete /></el-icon>
                                        一键清空
                                    </el-button>
                                </div>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <el-tag
                                        v-for="(word, index) in visibleGroupTriggerKeywords"
                                        :key="`${word}-${index}`"
                                        closable
                                        type="info"
                                        effect="light"
                                        round
                                        class="cursor-pointer"
                                        @close="handleRemoveGroupTriggerKeyword(index)"
                                        @click="handleEditGroupTriggerKeyword(index)">
                                        {{ word }}
                                    </el-tag>
                                    <span
                                        v-if="!formData.group_trigger_keywords.length"
                                        class="text-xs text-[#9CA3AF] leading-[28px]">
                                        暂无触发词，请在下方添加
                                    </span>
                                    <el-button
                                        v-if="hiddenGroupTriggerKeywordCount && !showGroupTriggerKeywordsMore"
                                        size="small"
                                        round
                                        @click="showGroupTriggerKeywordsMore = true">
                                        +{{ hiddenGroupTriggerKeywordCount }} 个
                                        <el-icon class="ml-0.5"><ArrowDown /></el-icon>
                                    </el-button>
                                    <el-button
                                        v-if="
                                            showGroupTriggerKeywordsMore &&
                                            formData.group_trigger_keywords.length > GROUP_TRIGGER_KEYWORD_VISIBLE_LIMIT
                                        "
                                        size="small"
                                        round
                                        @click="showGroupTriggerKeywordsMore = false">
                                        收起
                                        <el-icon class="ml-0.5"><ArrowUp /></el-icon>
                                    </el-button>
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <el-input
                                        v-model="groupTriggerKeywordInput"
                                        placeholder="输入触发词，回车添加"
                                        maxlength="20"
                                        show-word-limit
                                        @keyup.enter="handleAddGroupTriggerKeyword" />
                                    <el-button type="primary" @click="handleAddGroupTriggerKeyword">添加</el-button>
                                </div>
                                <p class="text-xs text-[#9CA3AF] mt-2 m-0">
                                    客户聊天中命中以下任一关键词时触发自动拉群
                                </p>
                            </template>
                        </div>

                        <!-- 销售微信 -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-[#212121]">指定拉入的销售微信（真人）</span>
                                <span class="text-xs text-[#b4b4b4]">{{ formData.sales_wechat.length }} / 5</span>
                            </div>
                            <p class="text-xs text-[#b4b4b4] mb-2 m-0">
                                机器人建群后，会自动将其拉入群聊中作为主理人。
                            </p>
                            <el-input
                                v-model="groupSalesInput"
                                placeholder="请输入微信号并按回车添加"
                                @keyup.enter="handleAddGroupSales"
                                clearable>
                                <template #suffix>
                                    <el-button link type="primary" @click="handleAddGroupSales">添加</el-button>
                                </template>
                            </el-input>
                            <div class="flex flex-wrap gap-2 mt-2" v-if="formData.sales_wechat.length > 0">
                                <el-tag
                                    v-for="(item, index) in formData.sales_wechat"
                                    :key="index"
                                    closable
                                    type="primary"
                                    @close="handleRemoveGroupSales(index)">
                                    {{ item }}
                                </el-tag>
                            </div>
                            <div
                                class="bg-[#FFF8F0] border border-[#FFE0B2] rounded-lg px-3 py-2 mt-2 flex items-start gap-2">
                                <el-icon color="#FF8C00" class="mt-0.5 flex-shrink-0"><WarningFilled /></el-icon>
                                <p class="text-xs text-[#CC5500] leading-relaxed m-0">
                                    强烈建议输入【微信号】或在机器人端统一设置好【备注名】，避免因昵称包含特殊符号导致拉人失败。
                                </p>
                            </div>
                        </div>

                        <!-- 群名称模板 -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-bold text-[#212121]">群名称模板</span>
                                <span class="text-xs text-[#b4b4b4]"
                                    >{{ formData.group_name_template.length }} / 32</span
                                >
                            </div>
                            <el-input
                                v-model="formData.group_name_template"
                                placeholder="请输入群名称模板"
                                :maxlength="32"
                                show-word-limit />
                            <div class="flex flex-wrap gap-2 mt-2">
                                <el-button size="small" @click="insertGroupNameTemplate('{客户名}')"
                                    >+ 插入客户名</el-button
                                >
                                <el-button size="small" @click="insertGroupNameTemplate('{销售名}')"
                                    >+ 插入销售名</el-button
                                >
                                <el-button size="small" @click="insertGroupNameTemplate('{日期}')"
                                    >+ 插入日期</el-button
                                >
                            </div>
                        </div>

                        <!-- 建群欢迎语 -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-[#212121]">建群后自动发送欢迎语</span>
                                <el-switch v-model="formData.is_greeting" :active-value="1" :inactive-value="0" />
                            </div>
                            <template v-if="formData.is_greeting === 1">
                                <rich-textarea
                                    v-model="formData.greeting_text"
                                    :max-length="500"
                                    placeholder="请输入建群欢迎语..." />
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <el-button size="small" @click="insertWelcomeContent('{客户名}')"
                                        >+ 插入客户名</el-button
                                    >
                                    <el-button size="small" @click="insertWelcomeContent('@{客户}')">+ @客户</el-button>
                                </div>
                            </template>
                        </div>

                        <!-- 携带历史聊天记录 -->
                        <div class="flex items-center justify-between border-t border-[#F0F2F5] pt-4">
                            <div>
                                <span class="text-sm font-bold text-[#0D1117]">携带历史聊天记录</span>
                                <p class="text-xs text-[#9CA3AF] m-0 mt-1 leading-relaxed">
                                    建群后，自动将拉群前的单聊历史记录同步转发至新群聊中
                                </p>
                            </div>
                            <el-switch v-model="formData.is_share_chats" :active-value="1" :inactive-value="0" />
                        </div>
                    </div>
                </template>
            </field-block>

            <el-dialog v-model="groupTriggerEditVisible" title="编辑触发词" width="380px" append-to-body>
                <el-input v-model="groupTriggerEditValue" maxlength="20" show-word-limit />
                <template #footer>
                    <el-button @click="groupTriggerEditVisible = false">取消</el-button>
                    <el-button type="primary" @click="handleConfirmGroupTriggerEdit">保存</el-button>
                </template>
            </el-dialog>

            <!-- ───────────── 防封控与频率限制 ───────────── -->
            <field-block icon="🛡️" icon-bg="linear-gradient(90deg,#4facfe 0%,#00f2fe 100%)" title="防封控与频率限制">
                <div class="bg-[#E6F0FF]/60 rounded-lg p-3 mb-4">
                    <p class="text-xs text-primary leading-relaxed m-0">
                        已开启"拟人随机停顿"。每次互动后，系统将随机停留 30秒~2分钟，模拟真人浏览行为，降低风控风险。
                    </p>
                </div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-bold text-[#212121]">每天互动人数（仅互动当天）</span>
                    <span class="text-lg font-extrabold text-primary">{{ formData.number }} 人</span>
                </div>
                <el-slider v-model="formData.number" :min="1" :max="30" show-stops />
                <div class="flex items-center justify-between mt-1">
                    <span class="text-xs text-[#b4b4b4]">保守（防封）</span>
                    <span class="text-xs text-[#b4b4b4]">激进（易封）</span>
                </div>
            </field-block>

            <!-- ───────────── 朋友圈发布设置 ───────────── -->
            <field-block
                icon="📸"
                icon-bg="linear-gradient(90deg,#f7971e 0%,#ffd200 100%)"
                title="朋友圈发布设置"
                v-if="false">
                <!-- 内容生成方式 Tab -->
                <div class="mb-4">
                    <p class="text-xs font-bold text-[#212121] mb-2 m-0">内容生成设置</p>
                    <div class="flex gap-2">
                        <div
                            v-for="tab in tabs"
                            :key="tab.value"
                            class="px-4 py-1.5 rounded-full text-xs font-bold cursor-pointer transition-all border"
                            :class="
                                activeTab === tab.value
                                    ? 'bg-primary text-white border-primary'
                                    : 'bg-white text-[#9CA3AF] border-[#e5e7eb]'
                            "
                            @click="handleSelectTab(tab.value)">
                            {{ tab.label }}
                        </div>
                    </div>
                </div>

                <!-- AI自动创作说明 -->
                <div v-if="activeTab === TabEnum.AI_AUTO" class="bg-white rounded-xl p-4 border border-[#ececec] mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-bold text-[#212121]">AI创作方向</span>
                        <span class="text-xs text-[#00C08E] font-bold">结合IP人设</span>
                    </div>
                    <p class="text-xs text-[#9CA3AF] leading-relaxed m-0">
                        AI自动从素材库里抽取内容，并配上符合人设的文案，自动防折叠。
                    </p>
                </div>

                <!-- 素材库内容 -->
                <div v-loading="aiIpLoading">
                    <!-- 视频素材 -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-[#212121]">
                                视频素材（{{ activeTab === TabEnum.AI_IP ? aiIpVideoList.length : videoList.length }}）
                            </span>
                        </div>

                        <!-- 指定素材模式 -->
                        <div v-if="activeTab === TabEnum.MANUAL" class="flex flex-wrap gap-2">
                            <div
                                class="w-20 h-20 rounded-xl bg-[#F8F9FD] border border-dashed border-[#D0E6FF] flex flex-col items-center justify-center gap-1 cursor-pointer hover:bg-[#EEF5FF] transition-colors"
                                @click="handleAddVideo">
                                <el-icon color="#0065fb" :size="20"><Plus /></el-icon>
                                <span class="text-xs text-primary">添加</span>
                            </div>
                            <div
                                v-for="(item, index) in videoList"
                                :key="index"
                                class="w-20 h-20 rounded-xl bg-[#E6F0FF] relative overflow-hidden group">
                                <el-image v-if="item.pic" :src="item.pic" fit="cover" class="w-full h-full" />
                                <div
                                    class="absolute inset-0 bg-[#000000]/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                                    <el-icon color="#fff" class="cursor-pointer" @click="handlePlayVideo(item.url)"
                                        ><VideoPlay
                                    /></el-icon>
                                    <el-icon color="#fff" class="cursor-pointer" @click="handleDeleteVideo(index)"
                                        ><Delete
                                    /></el-icon>
                                </div>
                            </div>
                        </div>

                        <!-- AI IP素材库模式 -->
                        <div v-else-if="activeTab === TabEnum.AI_IP">
                            <div v-if="aiIpVideoList.length === 0" class="flex flex-col items-center py-6 gap-2">
                                <el-icon :size="40" color="#D0D5DD"><VideoCamera /></el-icon>
                                <span class="text-xs text-[#9CA3AF]">暂无视频素材</span>
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <div
                                    v-for="(item, index) in aiIpVideoList"
                                    :key="index"
                                    class="w-20 h-20 rounded-xl bg-[#E6F0FF] relative overflow-hidden group cursor-pointer">
                                    <el-image v-if="item.pic" :src="item.pic" fit="cover" class="w-full h-full" />
                                    <div
                                        class="absolute inset-0 bg-[#000000]/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <el-icon color="#fff" @click="handlePlayVideo(item.url)"><VideoPlay /></el-icon>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 图片素材 -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-[#212121]">
                                图片素材（{{ activeTab === TabEnum.AI_IP ? aiIpImageList.length : imageList.length }}）
                            </span>
                        </div>

                        <!-- 指定素材模式 -->
                        <div v-if="activeTab === TabEnum.MANUAL" class="flex flex-wrap gap-2">
                            <div
                                class="w-20 h-20 rounded-xl bg-[#F8F9FD] border border-dashed border-[#D0E6FF] flex flex-col items-center justify-center gap-1 cursor-pointer hover:bg-[#EEF5FF] transition-colors"
                                @click="handleAddImage">
                                <el-icon color="#0065fb" :size="20"><Plus /></el-icon>
                                <span class="text-xs text-primary">添加</span>
                            </div>
                            <div
                                v-for="(item, index) in imageList"
                                :key="index"
                                class="w-20 h-20 rounded-xl bg-[#E6F0FF] relative overflow-hidden group cursor-pointer"
                                @click="handlePreviewImage(index)">
                                <el-image v-if="item.url" :src="item.url" fit="cover" class="w-full h-full" />
                                <div
                                    class="absolute inset-0 bg-[#000000]/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <el-icon color="#fff" @click.stop="handleDeleteImage(index)"><Delete /></el-icon>
                                </div>
                            </div>
                        </div>

                        <!-- AI IP素材库模式 -->
                        <div v-else-if="activeTab === TabEnum.AI_IP">
                            <div v-if="aiIpImageList.length === 0" class="flex flex-col items-center py-6 gap-2">
                                <el-icon :size="40" color="#D0D5DD"><Picture /></el-icon>
                                <span class="text-xs text-[#9CA3AF]">暂无图片素材</span>
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <div
                                    v-for="(item, index) in aiIpImageList"
                                    :key="index"
                                    class="w-20 h-20 rounded-xl bg-[#E6F0FF] relative overflow-hidden cursor-pointer"
                                    @click="handlePreviewAiImage(index)">
                                    <el-image v-if="item.url" :src="item.url" fit="cover" class="w-full h-full" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </field-block>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { defineComponent, h, ref as vRef, computed as vComputed } from "vue";
import {
    WarningFilled,
    InfoFilled,
    Star,
    ChatDotRound,
    CircleCheckFilled,
    ArrowRight,
    ArrowDown,
    ArrowUp,
    Plus,
    VideoPlay,
    Delete,
    VideoCamera,
    Picture,
} from "@element-plus/icons-vue";
import { ElInput, ElSwitch, ElIcon, ElButton, ElSlider, ElTag, ElImage } from "element-plus";
import {
    getInteractionConfig,
    updateInteractionConfig,
    getMaterialList,
    updatePersonOption,
} from "@/api/ai_application/device/person";
import Popup from "@/components/popup/index.vue";
import { setFormData } from "@/utils/util";
import { useLockFn } from "@/hooks/useLockFn";
import feedback from "@/utils/feedback";

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();

// ════════════════════════════════════════════════════
// 内联子组件：FieldBlock —— 区块标题行
// ════════════════════════════════════════════════════
const FieldBlock = defineComponent({
    name: "FieldBlock",
    props: {
        icon: { type: String, default: "" },
        iconBg: { type: String, default: "#E6F0FF" },
        title: { type: String, required: true },
        required: { type: Boolean, default: false },
        tip: { type: String, default: "" },
        badgeText: { type: String, default: "" },
        badgeClass: { type: String, default: "" },
    },
    setup(props, { slots }) {
        return () =>
            h("div", { class: "mb-5" }, [
                h("div", { class: "flex items-center gap-2 mb-2 px-1" }, [
                    h(
                        "div",
                        {
                            class: "w-7 h-7 rounded-full flex items-center justify-center text-sm flex-shrink-0",
                            style: { background: props.iconBg },
                        },
                        props.icon,
                    ),
                    h("span", { class: "text-sm font-extrabold text-[#171717]" }, props.title),
                    slots.extra
                        ? h(
                              "div",
                              { class: "ml-auto", onClick: (event: Event) => event.stopPropagation() },
                              slots.extra(),
                          )
                        : h(
                              "div",
                              {
                                  class: `ml-auto px-2 py-0.5 rounded-full text-xs font-bold ${
                                      props.badgeText
                                          ? props.badgeClass
                                          : props.required
                                          ? "bg-[#fef2f2] text-[#ef4444]"
                                          : "bg-[#eff6ff] text-primary"
                                  }`,
                              },
                              props.badgeText || (props.required ? "必填" : "选填"),
                          ),
                ]),
                h("div", { class: "bg-white rounded-xl p-4 border border-[#ececec] shadow-sm" }, [
                    props.tip ? h("p", { class: "text-xs text-[#b4b4b4] leading-relaxed mb-3 m-0" }, props.tip) : null,
                    slots.default?.(),
                ]),
            ]);
    },
});

// ════════════════════════════════════════════════════
// 内联子组件：RichTextarea —— 带示例占位 + 字数统计
// ════════════════════════════════════════════════════
const RichTextarea = defineComponent({
    name: "RichTextarea",
    props: {
        modelValue: { type: String, default: "" },
        maxLength: { type: Number, default: 2000 },
        placeholder: { type: String, default: "请输入" },
        examples: { type: Array as () => string[], default: () => [] },
    },
    emits: ["update:modelValue"],
    setup(props, { emit }) {
        const focused = vRef(false);
        const showPlaceholder = vComputed(() => !props.modelValue && !focused.value);

        return () =>
            h(
                "div",
                {
                    class: `relative rounded-lg px-3 py-2.5 border transition-colors ${
                        focused.value ? "bg-[#eff6ff]/30 border-primary" : "bg-[#f9f9f9] border-[#e3e3e3]"
                    }`,
                },
                [
                    showPlaceholder.value
                        ? h("div", { class: "absolute top-3 left-3 right-3 pointer-events-none select-none" }, [
                              h("p", { class: "text-xs text-[#cdcdcd] mb-1.5 m-0" }, props.placeholder),
                              props.examples.length > 0
                                  ? h("p", { class: "text-xs text-[#cdcdcd] mb-1 m-0" }, "例如：")
                                  : null,
                              ...props.examples.map((ex) =>
                                  h("p", { class: "text-xs text-[#cdcdcd] leading-relaxed mb-0.5 m-0" }, `· ${ex}`),
                              ),
                          ])
                        : null,
                    h(ElInput, {
                        modelValue: props.modelValue,
                        type: "textarea",
                        rows: 4,
                        maxlength: props.maxLength,
                        resize: "none",
                        class: "rich-textarea",
                        style: { background: "transparent" },
                        "onUpdate:modelValue": (val: string) => emit("update:modelValue", val),
                        onFocus: () => {
                            focused.value = true;
                        },
                        onBlur: () => {
                            focused.value = false;
                        },
                    }),
                    h("div", { class: "flex justify-end mt-1" }, [
                        h("span", { class: "text-xs text-[#cdcdcd]" }, `${props.modelValue.length}/${props.maxLength}`),
                    ]),
                ],
            );
    },
});

// ════════════════════════════════════════════════════
// Tab 枚举
// ════════════════════════════════════════════════════
enum TabEnum {
    AI_AUTO = "ai_auto",
    MANUAL = "manual",
    AI_IP = "ai_ip",
}

const tabs = [
    { label: "AI自动创作", value: TabEnum.AI_AUTO },
    { label: "指定素材", value: TabEnum.MANUAL },
    { label: "AI创作（IP素材库）", value: TabEnum.AI_IP },
];

// ════════════════════════════════════════════════════
// 主逻辑
// ════════════════════════════════════════════════════
const loading = ref(false);
const personId = ref<string>("");
const globalOption = ref<Record<string, any>>({});
const privateSwitch = reactive({
    add: true,
    group: true,
});

// ─── 表单数据 ────────────────────────────────────────
interface MediaItem {
    url: string;
    pic: string;
}

const formData = reactive({
    clue_count: 0,
    add_friend_script: "",
    // 自动加群
    is_auto_group: 0 as 0 | 1,
    group_trigger_mode: 1 as 1 | 2,
    group_trigger_keywords: ["加群", "进群"] as string[],
    sales_wechat: [] as string[],
    group_name_template: "{客户名}的专属VIP服务群",
    is_greeting: 1 as 0 | 1,
    greeting_text: "哈喽{客户名}，欢迎！我是您的专属销售顾问，以后有任何问题都可以直接在这个群里找我哦~",
    is_share_chats: 0 as 0 | 1,
    // 朋友圈互动
    is_like: 1,
    is_comment: 1,
    comment_method: 1,
    comment_speech: [] as string[],
    comment_robot_prompt: "",
    number: 15,
});

// ─── 自动加群 ────────────────────────────────────────
const groupSalesInput = ref<string>("");
const groupTriggerKeywordInput = ref("");
const groupTriggerEditVisible = ref(false);
const groupTriggerEditValue = ref("");
const groupTriggerEditIndex = ref(-1);
const GROUP_TRIGGER_KEYWORD_VISIBLE_LIMIT = 3;
const showGroupTriggerKeywordsMore = ref(false);
const visibleGroupTriggerKeywords = computed(() =>
    showGroupTriggerKeywordsMore.value
        ? formData.group_trigger_keywords
        : formData.group_trigger_keywords.slice(0, GROUP_TRIGGER_KEYWORD_VISIBLE_LIMIT),
);
const hiddenGroupTriggerKeywordCount = computed(() =>
    Math.max(formData.group_trigger_keywords.length - GROUP_TRIGGER_KEYWORD_VISIBLE_LIMIT, 0),
);

const normalizeStringList = (raw: unknown, fallback: string[] = []): string[] => {
    if (!Array.isArray(raw)) return fallback;
    return raw.map((item) => String(item ?? "").trim()).filter(Boolean);
};

const handleAddGroupTriggerKeyword = (): void => {
    const val = groupTriggerKeywordInput.value.trim();
    if (!val) return;
    if (formData.group_trigger_keywords.includes(val)) {
        feedback.msgWarning("该触发词已存在");
        return;
    }
    formData.group_trigger_keywords.push(val);
    groupTriggerKeywordInput.value = "";
};

const handleRemoveGroupTriggerKeyword = (index: number): void => {
    formData.group_trigger_keywords.splice(index, 1);
};

const handleClearAllGroupTriggerKeywords = async (): Promise<void> => {
    if (!formData.group_trigger_keywords.length) return;
    try {
        await feedback.confirm("确定清空全部自定义触发词吗？");
        formData.group_trigger_keywords = [];
        showGroupTriggerKeywordsMore.value = false;
    } catch {
        // 用户取消
    }
};

const handleEditGroupTriggerKeyword = (index: number): void => {
    groupTriggerEditIndex.value = index;
    groupTriggerEditValue.value = formData.group_trigger_keywords[index] ?? "";
    groupTriggerEditVisible.value = true;
};

const handleConfirmGroupTriggerEdit = (): void => {
    const val = groupTriggerEditValue.value.trim();
    if (!val) {
        feedback.msgWarning("内容不能为空");
        return;
    }
    if (formData.group_trigger_keywords.some((item, index) => item === val && index !== groupTriggerEditIndex.value)) {
        feedback.msgWarning("该触发词已存在");
        return;
    }
    formData.group_trigger_keywords[groupTriggerEditIndex.value] = val;
    groupTriggerEditVisible.value = false;
    groupTriggerEditValue.value = "";
    groupTriggerEditIndex.value = -1;
};

watch(
    () => formData.group_trigger_mode,
    () => {
        showGroupTriggerKeywordsMore.value = false;
    },
);

const handleAddGroupSales = (): void => {
    const val = groupSalesInput.value.trim();
    if (!val) return;
    if (formData.sales_wechat.length >= 5) {
        feedback.msgWarning("最多添加5个销售微信");
        return;
    }
    if (formData.sales_wechat.includes(val)) {
        feedback.msgWarning("该微信号已添加");
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
        feedback.msgWarning("群名称模板最多32个字符");
        return;
    }
    formData.group_name_template += variable;
};

const insertWelcomeContent = (variable: string): void => {
    formData.greeting_text += variable;
};

// ─── 评论提示词 ──────────────────────────────────────
const showPromptEdit = ref(false);

// ─── 固定话术编辑 ────────────────────────────────────
const speechEditVisible = ref(false);
const speechEditValue = ref("");
const speechEditIndex = ref(-1);

const handleEditSpeech = (index: number): void => {
    speechEditIndex.value = index;
    speechEditValue.value = index > -1 ? formData.comment_speech[index] : "";
    speechEditVisible.value = true;
};

const handleSpeechConfirm = (): void => {
    const val = speechEditValue.value.trim();
    if (!val) return;
    if (speechEditIndex.value === -1) {
        formData.comment_speech.push(val);
    } else {
        formData.comment_speech[speechEditIndex.value] = val;
    }
    speechEditVisible.value = false;
    speechEditValue.value = "";
    speechEditIndex.value = -1;
};

// ─── 朋友圈发布 Tab ──────────────────────────────────
const activeTab = ref<TabEnum>(TabEnum.AI_AUTO);
const aiIpLoading = ref(false);
const videoList = ref<MediaItem[]>([]);
const imageList = ref<MediaItem[]>([]);
const aiIpVideoList = ref<MediaItem[]>([]);
const aiIpImageList = ref<MediaItem[]>([]);

const handleSelectTab = async (value: TabEnum): Promise<void> => {
    if (activeTab.value === value) return;
    activeTab.value = value;
    if (value !== TabEnum.AI_IP) return;
    aiIpVideoList.value = [];
    aiIpImageList.value = [];
    aiIpLoading.value = true;
    try {
        const { lists } = await getMaterialList({ persona_id: personId.value, page_size: 100 });
        aiIpVideoList.value = lists
            .filter((item: any) => item.material_type === 1)
            .slice(0, 4)
            .map((item: any) => ({ url: item.file_url, pic: item.thumbnail_url }));
        aiIpImageList.value = lists
            .filter((item: any) => item.material_type === 2)
            .slice(0, 4)
            .map((item: any) => ({ url: item.file_url, pic: item.thumbnail_url }));
    } finally {
        aiIpLoading.value = false;
    }
};

const handleAddVideo = (): void => {
    /* 调用上传组件 */
};
const handleDeleteVideo = (index: number): void => {
    videoList.value.splice(index, 1);
};
const handlePlayVideo = (url: string): void => {
    /* 调用视频预览组件 */
};
const handleAddImage = (): void => {
    /* 调用上传组件 */
};
const handleDeleteImage = (index: number): void => {
    imageList.value.splice(index, 1);
};
const handlePreviewImage = (index: number): void => {
    ElImage.prototype; // placeholder
};
const handlePreviewAiImage = (index: number): void => {
    ElImage.prototype; // placeholder
};

// ─── 表单校验 ────────────────────────────────────────
const validateForm = (): string | null => {
    if (privateSwitch.add && !formData.add_friend_script.trim()) return "请输入好友验证申请话术";
    if (formData.comment_method === 1 && !formData.comment_robot_prompt.trim()) return "请设置评论机器人提示词";
    if (formData.comment_method === 2 && formData.comment_speech.length === 0) return "请至少添加一条固定话术";
    if (privateSwitch.group) {
        if (formData.group_trigger_mode === 2 && formData.group_trigger_keywords.length === 0) {
            return "请添加至少一个加群触发词";
        }
        if (formData.sales_wechat.length === 0) return "请添加至少一个销售微信";
        if (!formData.group_name_template.trim()) return "请输入群名称模板";
        if (formData.is_greeting === 1 && !formData.greeting_text.trim()) return "请输入建群欢迎语内容";
    }
    return null;
};

const toSwitchBool = (val: any, fallback = false): boolean => {
    if (val === 1 || val === "1" || val === true) return true;
    if (val === 0 || val === "0" || val === false) return false;
    return fallback;
};

const applyPrivateSwitch = (config: Record<string, any> = {}): void => {
    const options = globalOption.value?.private_operation?.options || {};
    privateSwitch.add = toSwitchBool(options.add_friend, true);
    privateSwitch.group = toSwitchBool(options.auto_add_group, toSwitchBool(config.is_auto_group, true));
};

const buildGlobalOptionPayload = (): Record<string, any> => {
    const existing = globalOption.value || {};
    const privateOperation = existing.private_operation || {};
    return {
        ...existing,
        private_operation: {
            ...privateOperation,
            options: {
                ...(privateOperation.options || {}),
                add_friend: privateSwitch.add ? 1 : 0,
                auto_add_group: privateSwitch.group ? 1 : 0,
            },
        },
    };
};

// ─── 保存 ────────────────────────────────────────────
const handleSave = async (): Promise<void> => {
    const errMsg = validateForm();
    if (errMsg) {
        feedback.msgWarning(errMsg);
        return;
    }
    await Promise.all([
        updateInteractionConfig({
            persona_id: personId.value,
            ...formData,
            is_auto_group: privateSwitch.group ? 1 : 0,
        }),
        updatePersonOption({
            id: personId.value,
            global_option: buildGlobalOptionPayload(),
        }),
    ]);
    close();
    emit("success");
};
const { isLock, lockFn } = useLockFn(handleSave);

// ─── 初始化数据 ──────────────────────────────────────
const getDetail = async (): Promise<void> => {
    loading.value = true;
    try {
        const config = await getInteractionConfig({ id: personId.value });
        setFormData(config, formData);
        formData.group_trigger_mode = Number(config?.group_trigger_mode) === 2 ? 2 : 1;
        formData.group_trigger_keywords = normalizeStringList(config?.group_trigger_keywords, ["加群", "进群"]);
        applyPrivateSwitch(config || {});
    } finally {
        loading.value = false;
    }
};

// ─── 对外暴露 ────────────────────────────────────────
const open = (id: string, option: Record<string, any> = {}): void => {
    personId.value = id;
    globalOption.value = option || {};
    groupTriggerKeywordInput.value = "";
    groupTriggerEditVisible.value = false;
    showGroupTriggerKeywordsMore.value = false;
    getDetail();
    popupRef.value?.open();
};

const close = (): void => {
    emit("close");
};

defineExpose({ open });
</script>

<style scoped>
:deep(.rich-textarea .el-textarea__inner) {
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 0;
    font-size: 13px;
    line-height: 1.8;
    color: #374151;
    resize: none;
}
</style>
