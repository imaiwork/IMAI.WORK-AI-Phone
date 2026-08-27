<template>
    <el-drawer
        v-model="visible"
        :title="mode === 'add' ? '新增模板' : '编辑模板'"
        direction="rtl"
        size="860px"
        :before-close="closeDrawer"
        :close-on-click-modal="true">
        <div class="space-y-10 pb-4" @click="closeAllDropdowns">
            <section>
                <SectionTitle title="基础信息" />
                <div class="grid grid-cols-2 gap-6 mt-5">
                    <div class="col-span-2">
                        <FieldLabel :required="true">模板名称</FieldLabel>
                        <el-input v-model="formData.name" placeholder="例如：企服高客单转化流" maxlength="100" />
                        <p v-if="errors.name" class="err-tip">{{ errors.name }}</p>
                    </div>
                    <div>
                        <FieldLabel :required="true" extra="(对齐小程序Tab)">一级分类</FieldLabel>
                        <el-select
                            v-model="formData.category_id"
                            placeholder="输入关键词搜索分类"
                            filterable
                            remote
                            clearable
                            :remote-method="fetchCategories"
                            :loading="categoryLoading"
                            loading-text="搜索中..."
                            no-data-text="暂无匹配分类"
                            class="w-full"
                            @focus="fetchCategories('')">
                            <el-option
                                v-for="item in categoryOptions"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value" />
                        </el-select>
                        <p v-if="errors.category_id" class="err-tip">{{ errors.category_id }}</p>
                    </div>
                    <div>
                        <FieldLabel :required="true" extra="(对齐小程序标签)">运营偏好</FieldLabel>
                        <el-select v-model="formData.operation_preference" class="w-full">
                            <el-option
                                v-for="item in OPERATION_PREFERENCE_OPTIONS"
                                :key="item.value"
                                :label="item.label"
                                :value="item.value" />
                        </el-select>
                    </div>
                    <div class="col-span-2">
                        <FieldLabel :required="true">模板描述</FieldLabel>
                        <el-input
                            v-model="formData.description"
                            type="textarea"
                            :rows="2"
                            maxlength="200"
                            show-word-limit
                            placeholder="请输入模板描述..." />
                    </div>
                </div>
            </section>

            <section>
                <div class="flex items-center justify-between mb-5">
                    <SectionTitle title="任务节点配置" />
                    <el-button type="primary" plain size="small" :icon="Plus" @click="addTaskNode">
                        添加节点
                    </el-button>
                </div>

                <div class="border border-[#e5e7eb] rounded-lg overflow-visible">
                    <table class="w-full text-left">
                        <thead class="bg-[#f9fafb] border-b border-[#e5e7eb] text-xs text-[#6b7280]">
                            <tr>
                                <th class="p-3 w-14 text-center">序号</th>
                                <th class="p-3 w-36">任务类型</th>
                                <th class="p-3">执行平台 <span class="text-[#9ca3af]">(多选)</span></th>
                                <th class="p-3 w-32">开始时间</th>
                                <th class="p-3 w-32">结束时间</th>
                                <th class="p-3 w-14 text-center">操作</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#e5e7eb]">
                            <tr v-if="formData.schedule.length === 0">
                                <td colspan="6" class="p-8 text-center text-sm text-[#9ca3af]">
                                    暂无任务节点，请点击右上角添加
                                </td>
                            </tr>
                            <tr
                                v-for="(task, index) in formData.schedule"
                                :key="task._key"
                                :class="['hover:bg-[#f9fafb]', conflictKeys.has(task._key) ? 'bg-[#fff7ed]' : '']">
                                <td
                                    class="p-3 text-center text-xs font-medium"
                                    :class="conflictKeys.has(task._key) ? 'text-[#f97316]' : 'text-[#9ca3af]'">
                                    {{ String(index + 1).padStart(2, "0") }}
                                    <span v-if="conflictKeys.has(task._key)" title="时间冲突"> ⚠</span>
                                </td>
                                <td class="p-3">
                                    <el-select
                                        v-model="task.scene"
                                        size="small"
                                        class="w-full"
                                        @change="onTaskTypeChange(task)">
                                        <el-option
                                            v-for="item in getSceneOptions(task)"
                                            :key="item.value"
                                            :label="item.label"
                                            :value="item.value"
                                            :disabled="item.disabled" />
                                    </el-select>
                                </td>
                                <td class="p-3 relative" @click.stop>
                                    <div class="flex flex-wrap gap-1.5 items-center">
                                        <el-tag
                                            v-for="at in task.account_types"
                                            :key="at"
                                            size="small"
                                            :closable="getAvailablePlatforms(task.scene).length > 1"
                                            type="primary"
                                            @close="removePlatform(task, at)">
                                            {{ getPlatformName(task.scene, at) }}
                                        </el-tag>
                                        <el-button
                                            v-if="getAvailablePlatforms(task.scene).length > 1"
                                            size="small"
                                            plain
                                            @click.stop="toggleDropdown(task._key)">
                                            + 添加平台
                                        </el-button>
                                    </div>
                                    <p
                                        v-if="task.scene === 11 && task.account_types.includes(SPH_TYPE)"
                                        class="mt-1 text-xs text-[#f97316] flex items-center gap-1">
                                        <el-icon><Warning /></el-icon>
                                        视频号仅能点赞感谢
                                    </p>
                                    <div
                                        v-if="
                                            activeDropdownKey === task._key &&
                                            getAvailablePlatforms(task.scene).length > 1
                                        "
                                        class="absolute top-full left-0 mt-1 w-52 bg-[#ffffff] border border-[#e5e7eb] shadow-xl rounded-md z-20 p-2 grid grid-cols-2 gap-1"
                                        @click.stop>
                                        <label
                                            v-for="p in getAvailablePlatforms(task.scene)"
                                            :key="p.type"
                                            class="flex items-center gap-2 text-xs cursor-pointer hover:bg-[#f9fafb] p-1.5 rounded"
                                            @click.stop>
                                            <el-checkbox
                                                :model-value="task.account_types.includes(p.type)"
                                                @change="togglePlatform(task, p.type)" />
                                            <span class="text-[#374151]">{{ p.name }}</span>
                                        </label>
                                    </div>
                                </td>
                                <td class="p-3">
                                    <el-time-picker
                                        :model-value="safeTimeStrToDate(task.start_time)"
                                        format="HH:mm"
                                        placeholder="开始"
                                        size="small"
                                        style="width: 110px"
                                        :disabled-hours="() => getDisabledStartHours(task)"
                                        :disabled-minutes="(h: any) => getDisabledStartMinutes(task, h)"
                                        @update:model-value="(val: Date) => { task.start_time = safeDateToTimeStr(val); onStartTimeChange(task); }" />
                                    <p
                                        v-if="taskTimeErrors[task._key]?.start"
                                        class="mt-0.5 text-xs text-[#ef4444] w-28 leading-tight">
                                        {{ taskTimeErrors[task._key].start }}
                                    </p>
                                </td>
                                <td class="p-3">
                                    <template v-if="task.scene === SCENE_VIDEO_PUBLISH">
                                        <el-input
                                            :model-value="calcEndTime(task)"
                                            size="small"
                                            disabled
                                            title="视频发布根据平台数量自动计算锁定"
                                            style="width: 110px" />
                                    </template>
                                    <template v-else>
                                        <el-time-picker
                                            :model-value="safeTimeStrToDate(task.end_time)"
                                            format="HH:mm"
                                            placeholder="结束"
                                            size="small"
                                            style="width: 110px"
                                            :disabled-hours="() => getDisabledEndHours(task)"
                                            :disabled-minutes="(h: any) => getDisabledEndMinutes(task, h)"
                                            @update:model-value="(val: Date) => { task.end_time = safeDateToTimeStr(val); onEndTimeChange(task); }" />
                                        <!-- 结束时间行内错误 -->
                                        <p
                                            v-if="taskTimeErrors[task._key]?.end"
                                            class="mt-0.5 text-xs text-[#ef4444] w-28 leading-tight">
                                            {{ taskTimeErrors[task._key].end }}
                                        </p>
                                    </template>
                                </td>
                                <td class="p-3 text-center">
                                    <el-button
                                        type="danger"
                                        link
                                        size="small"
                                        :icon="Delete"
                                        @click="removeTaskNode(index)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="bg-[#eff6ff] p-3 text-xs text-[#3b82f6] flex items-start border-t border-[#dbeafe]">
                        <el-icon class="mr-1.5 mt-0.5 flex-shrink-0"><Warning /></el-icon>
                        <div>
                            <p class="font-bold mb-0.5">防呆规则提示：</p>
                            <p class="text-[#60a5fa]">
                                1. 00:00 - 06:00 为固定创作时间，不可排期。<br />
                                2.
                                【视频发布】结束时间由系统根据执行平台数量自动计算锁定（单平台10分钟），无需手动填写。<br />
                                3. 各任务节点的执行时间段不可重叠。<br />
                                4. 开始时间必须早于结束时间。<br />
                                5. 开始时间与结束时间间隔不得少于 5 分钟。
                            </p>
                        </div>
                    </div>
                </div>

                <p v-if="errors.schedule" class="err-tip mt-1">{{ errors.schedule }}</p>
            </section>

            <section class="pt-4 border-t border-[#f3f4f6]">
                <SectionTitle title="方案说明" />
                <div class="space-y-5 mt-5">
                    <div class="flex items-start gap-4">
                        <label class="w-20 text-sm text-[#374151] mt-2 shrink-0">适用场景</label>
                        <el-input
                            v-model="formData.detail_content"
                            type="textarea"
                            :rows="3"
                            maxlength="500"
                            show-word-limit
                            placeholder="请输入适用场景..." />
                    </div>
                </div>
            </section>
        </div>

        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold text-[#374151]">状态</span>
                    <el-switch
                        v-model="formData.status"
                        :active-value="1"
                        :inactive-value="0"
                        active-text="启用"
                        inactive-text="禁用" />
                </div>
                <div class="flex gap-3">
                    <el-button @click="closeDrawer">取消</el-button>
                    <el-button type="primary" :loading="isLock" @click="lockFn">确定</el-button>
                </div>
            </div>
        </template>
    </el-drawer>
</template>

<script setup lang="ts">
import { ref, reactive, computed, h } from "vue";
import { Delete, Plus, Warning } from "@element-plus/icons-vue";
import {
    getTaskTemplateCateList,
    addTaskTemplate,
    editTaskTemplate,
    getTaskTemplateDetail,
} from "@/api/ai_application/device/task_template";
import { getAutoTaskSceneConfig } from "@/api/ai_application/device/auto_task_scene";
import { AppTypeEnum } from "@/enums/appEnums";
import { useLockFn } from "@/hooks/useLockFn";
import feedback from "@/utils/feedback";

// ─── 内联子组件 ───────────────────────────────────────────────
const SectionTitle = (props: { title: string }) =>
    h("div", { class: "flex items-center" }, [
        h("div", { class: "w-1 h-4 bg-[#3b82f6] rounded-full mr-2" }),
        h("h3", { class: "font-bold text-base text-[#111827]" }, props.title),
    ]);

const FieldLabel = (props: { required?: boolean; extra?: string }, { slots }: { slots: any }) =>
    h("label", { class: "block text-xs font-bold text-[#374151] mb-2" }, [
        props.required ? h("span", { class: "text-[#ef4444] mr-1" }, "*") : null,
        slots.default?.(),
        props.extra ? h("span", { class: "text-[#9ca3af] font-normal ml-1" }, props.extra) : null,
    ]);

// ─── 平台枚举常量 ─────────────────────────────────────────────
const WX_TYPE = AppTypeEnum.WECHAT;
const SPH_TYPE = AppTypeEnum.SPH;
const XHS_TYPE = AppTypeEnum.XHS;
const DOYIN_TYPE = AppTypeEnum.DOUYIN;
const KUAISHOU_TYPE = AppTypeEnum.KUAISHOU;

// ─── 运营偏好选项 ─────────────────────────────────────────────
const OPERATION_PREFERENCE_OPTIONS = [
    { label: "综合", value: 1 },
    { label: "获客", value: 2 },
    { label: "养号", value: 3 },
    { label: "运营", value: 4 },
];

// ─── 平台类型定义 ─────────────────────────────────────────────
interface PlatformItem {
    type: number;
    name: string;
}

// ─── 场景配置 ────────────────────────────────────────────────
const SCENE_CONFIG: Record<number, { label: string; platforms: PlatformItem[] }> = {
    1: {
        label: "截流评论获客",
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
            { type: KUAISHOU_TYPE, name: "快手" },
        ],
    },
    2: {
        label: "截流私信获客",
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
            { type: KUAISHOU_TYPE, name: "快手" },
        ],
    },
    3: {
        label: "留痕获客",
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
        ],
    },
    4: {
        label: "视频号获客",
        platforms: [{ type: SPH_TYPE, name: "视频号" }],
    },
    5: {
        label: "视频发布",
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
            { type: KUAISHOU_TYPE, name: "快手" },
            { type: SPH_TYPE, name: "视频号" },
        ],
    },
    6: {
        label: "私信接管/个微接管",
        platforms: [
            { type: WX_TYPE, name: "微信" },
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
            { type: KUAISHOU_TYPE, name: "快手" },
        ],
    },
    7: {
        label: "朋友圈发布",
        platforms: [{ type: WX_TYPE, name: "微信" }],
    },
    8: {
        label: "朋友圈互动",
        platforms: [{ type: WX_TYPE, name: "微信" }],
    },
    9: {
        label: "自动加好友",
        platforms: [{ type: WX_TYPE, name: "微信" }],
    },
    10: {
        label: "自动养号",
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: KUAISHOU_TYPE, name: "快手" },
        ],
    },
    11: {
        label: "评论接管",
        platforms: [
            { type: DOYIN_TYPE, name: "抖音" },
            { type: XHS_TYPE, name: "小红书" },
            { type: KUAISHOU_TYPE, name: "快手" },
        ],
    },
    12: {
        label: "同城曝光",
        platforms: [{ type: DOYIN_TYPE, name: "抖音" }],
    },
    13: {
        label: "同城截流",
        platforms: [{ type: DOYIN_TYPE, name: "抖音" }],
    },
    14: {
        label: "团购截流",
        platforms: [{ type: DOYIN_TYPE, name: "抖音" }],
    },
    15: {
        label: "评论点赞",
        platforms: [{ type: SPH_TYPE, name: "视频号" }],
    },
};

interface SceneOption {
    value: number;
    label: string;
    disabled?: boolean;
}

const ALL_SCENE_OPTIONS: SceneOption[] = Object.entries(SCENE_CONFIG).map(([v, c]) => ({
    value: Number(v),
    label: c.label,
}));

/** 可选平台：本地平台配置 ∩ 后台「开放平台」开关；配置未加载或拉取失败时不过滤 */
const getAvailablePlatforms = (scene: number): PlatformItem[] => {
    const platforms = SCENE_CONFIG[scene]?.platforms ?? [];
    const openTypes = openPlatformMap.value.get(scene);
    if (!openTypes) return platforms;
    return platforms.filter((p) => openTypes.has(p.type));
};

/** 回显/提交时剔除已关闭平台，避免关快手后仍按旧平台数锁定结束时间 */
const filterOpenAccountTypes = (scene: number, accountTypes: number[]): number[] => {
    const openTypes = openPlatformMap.value.get(scene);
    if (!openTypes) return accountTypes;
    return accountTypes.filter((type) => openTypes.has(type));
};

const getPlatformName = (scene: number, type: number): string =>
    SCENE_CONFIG[scene]?.platforms.find((p) => p.type == type)?.name ?? String(type);

const SCENE_VIDEO_PUBLISH = 5;
const MINUTES_PER_PLATFORM = 10;
const DEFAULT_SCENE = 5;
const FORBIDDEN_ZONE_END = 6 * 60;
/** 开始与结束时间最小间隔（分钟） */
const MIN_DURATION_MINUTES = 5;

// ─── 类型定义 ────────────────────────────────────────────────
interface CategoryOption {
    label: string;
    value: number;
}

interface TaskNode {
    _key: number;
    scene: number;
    account_types: number[];
    start_time: string;
    end_time: string;
}

interface TemplateForm {
    id: string;
    name: string;
    category_id: number | null;
    operation_preference: number;
    status: number;
    description: string;
    detail_content: string;
    schedule: TaskNode[];
}

// ─── Props / Emits ───────────────────────────────────────────
defineProps<{ categories?: string[] }>();

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

// ─── 响应式状态 ──────────────────────────────────────────────
const visible = ref(false);
const mode = ref<"add" | "edit">("add");
const activeDropdownKey = ref<number | null>(null);
const categoryOptions = ref<CategoryOption[]>([]);
const categoryLoading = ref(false);
/** 允许添加的场景；未加载完成前为空，避免短暂露出已关闭类型 */
const allowAddSceneSet = ref<Set<number>>(new Set());
/** scene → 后台开放的平台 account_type；无该 scene 的键表示不过滤 */
const openPlatformMap = ref<Map<number, Set<number>>>(new Map());
const sceneConfigLoaded = ref(false);

const formData = reactive<TemplateForm>({
    id: "",
    name: "",
    category_id: null,
    operation_preference: 1,
    status: 1,
    description: "",
    detail_content: "",
    schedule: [],
});

const errors = reactive<Record<string, string>>({});

/**
 * 每行任务的时间行内错误，key 为 task._key
 * { [_key]: { start?: string; end?: string } }
 */
const taskTimeErrors = reactive<Record<number, { start?: string; end?: string }>>({});

/** 清除某个 task 的行内时间错误 */
const clearTaskTimeError = (key: number, field?: "start" | "end") => {
    if (!taskTimeErrors[key]) return;
    if (field) {
        delete taskTimeErrors[key][field];
    } else {
        delete taskTimeErrors[key];
    }
};

/** 设置某个 task 的行内时间错误 */
const setTaskTimeError = (key: number, field: "start" | "end", msg: string) => {
    if (!taskTimeErrors[key]) taskTimeErrors[key] = {};
    taskTimeErrors[key][field] = msg;
};

// ─── 时间字符串 ↔ Date 安全转换 ─────────────────────────────
const safeTimeStrToDate = (timeStr: string): Date | null => {
    if (!timeStr || timeStr === "--:--") return null;
    const match = timeStr.match(/^(\d{1,2}):(\d{2})/);
    if (!match) return null;
    const h = parseInt(match[1], 10);
    const m = parseInt(match[2], 10);
    if (isNaN(h) || isNaN(m)) return null;
    const d = new Date();
    d.setHours(h, m, 0, 0);
    return d;
};

const safeDateToTimeStr = (date: Date | null | undefined): string => {
    if (!date) return "";
    const h = String(date.getHours()).padStart(2, "0");
    const m = String(date.getMinutes()).padStart(2, "0");
    return `${h}:${m}`;
};

// ─── 一级分类：远程搜索 ──────────────────────────────────────
const fetchCategories = async (keyword: string) => {
    categoryLoading.value = true;
    try {
        const { lists } = await getTaskTemplateCateList({ name: keyword });
        categoryOptions.value = (lists ?? []).map((item: any) => ({
            label: item.name,
            value: item.id,
        }));
    } catch {
        categoryOptions.value = [];
    } finally {
        categoryLoading.value = false;
    }
};

// ─── 任务类型允许添加配置 ────────────────────────────────────
const fetchSceneAllowConfig = async () => {
    sceneConfigLoaded.value = false;
    try {
        const res = await getAutoTaskSceneConfig();
        const list = Array.isArray(res?.items) ? res.items : [];
        allowAddSceneSet.value = new Set(
            list
                .filter((item: any) => Number(item.allow_add) === 1)
                .map((item: any) => Number(item.scene)),
        );
        const platformMap = new Map<number, Set<number>>();
        list.forEach((item: any) => {
            if (!Array.isArray(item.allow_platforms)) return;
            platformMap.set(
                Number(item.scene),
                new Set(
                    item.allow_platforms
                        .filter((platform: any) => Number(platform.status) === 1)
                        .map((platform: any) => Number(platform.account_type)),
                ),
            );
        });
        openPlatformMap.value = platformMap;
    } catch {
        // 配置拉取失败时不拦截编辑，回退展示全部类型与全部平台
        allowAddSceneSet.value = new Set(ALL_SCENE_OPTIONS.map((item) => item.value));
        openPlatformMap.value = new Map();
    } finally {
        sceneConfigLoaded.value = true;
    }
};

// 平台全部关闭的类型同样不可添加：加了也不会生成 24h 任务
const addableSceneOptions = computed<SceneOption[]>(() =>
    ALL_SCENE_OPTIONS.filter(
        (item) => allowAddSceneSet.value.has(item.value) && getAvailablePlatforms(item.value).length > 0,
    ),
);

/**
 * 下拉选项：仅 allow_add=1 且有开放平台；
 * 当前行若已是关闭类型，仅临时回显该选项（禁用），避免污染「添加节点」可选列表。
 */
const getSceneOptions = (task: TaskNode): SceneOption[] => {
    const options = addableSceneOptions.value.map((item) => ({ ...item, disabled: false }));
    if (options.some((item) => item.value === task.scene)) return options;
    const current = ALL_SCENE_OPTIONS.find((item) => item.value === task.scene);
    if (!current) return options;
    return [
        ...options,
        {
            ...current,
            label: `${current.label}（已关闭）`,
            disabled: true,
        },
    ];
};

const getDefaultScene = (): number | null => {
    const options = addableSceneOptions.value;
    if (!options.length) return null;
    if (options.some((item) => item.value === DEFAULT_SCENE)) return DEFAULT_SCENE;
    return options[0].value;
};

// ─── 工具函数 ────────────────────────────────────────────────
const timeToMinutes = (t: string): number => {
    if (!t || t === "--:--") return -1;
    const [h, m] = t.split(":").map(Number);
    return h * 60 + m;
};

const minutesToTime = (total: number): string => {
    const h = Math.floor(total / 60) % 24;
    const m = total % 60;
    return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}`;
};

const parseTime = (t: string): { hour: number; minute: number } | null => {
    if (!t || t === "--:--") return null;
    const [h, m] = t.split(":").map(Number);
    if (isNaN(h) || isNaN(m)) return null;
    return { hour: h, minute: m };
};

// ─── 结束时间计算（仅视频发布 scene=5）──────────────────────
const calcEndTime = (task: TaskNode): string => {
    if (!task.start_time || task.account_types.length === 0) return "--:--";
    const total = timeToMinutes(task.start_time) + task.account_types.length * MINUTES_PER_PLATFORM;
    return minutesToTime(total);
};

const getEndTime = (task: TaskNode): string => (task.scene === SCENE_VIDEO_PUBLISH ? calcEndTime(task) : task.end_time);

const syncVideoPublishEndTime = (task: TaskNode) => {
    if (task.scene !== SCENE_VIDEO_PUBLISH) return;
    const endTime = calcEndTime(task);
    if (endTime !== "--:--") task.end_time = endTime;
};

// ─── 校验单个任务的时间间隔（≥ 5 分钟）─────────────────────
const checkDuration = (task: TaskNode): boolean => {
    // 视频发布由系统计算，不做此校验
    if (task.scene === SCENE_VIDEO_PUBLISH) return true;
    if (!task.start_time || !task.end_time) return true;
    const diff = timeToMinutes(task.end_time) - timeToMinutes(task.start_time);
    return diff >= MIN_DURATION_MINUTES;
};

// ─── 自动寻找空闲开始时间 ────────────────────────────────────
const findFreeStartTime = (durationMinutes: number): string => {
    const DAY_END = 24 * 60;
    const occupied = formData.schedule
        .map((t) => ({
            start: timeToMinutes(t.start_time),
            end: timeToMinutes(getEndTime(t)),
        }))
        .filter((seg) => seg.start >= 0 && seg.end > seg.start)
        .sort((a, b) => a.start - b.start);

    let cursor = FORBIDDEN_ZONE_END;
    for (const seg of occupied) {
        if (cursor + durationMinutes <= seg.start) break;
        if (seg.end > cursor) cursor = seg.end;
    }
    if (cursor + durationMinutes > DAY_END) cursor = FORBIDDEN_ZONE_END;
    return minutesToTime(cursor);
};

// ─── 开始时间 disabled 控制 ──────────────────────────────────
const getDisabledStartHours = (task: TaskNode): number[] => {
    const forbidden = Array.from({ length: 6 }, (_, i) => i);
    const end = parseTime(task.end_time);
    if (!end) return forbidden;
    const afterEnd = Array.from({ length: 24 }, (_, i) => i).filter((h) => h > end.hour);
    return [...new Set([...forbidden, ...afterEnd])];
};

const getDisabledStartMinutes = (task: TaskNode, selectedHour: number): number[] => {
    const end = parseTime(task.end_time);
    if (!end || selectedHour !== end.hour) return [];
    return Array.from({ length: 60 }, (_, i) => i).filter((m) => m >= end.minute);
};

// ─── 结束时间 disabled 控制 ──────────────────────────────────
const getDisabledEndHours = (task: TaskNode): number[] => {
    const start = parseTime(task.start_time);
    if (!start) return [];
    return Array.from({ length: 24 }, (_, i) => i).filter((h) => h < start.hour);
};

const getDisabledEndMinutes = (task: TaskNode, selectedHour: number): number[] => {
    const start = parseTime(task.start_time);
    if (!start || selectedHour !== start.hour) return [];
    return Array.from({ length: 60 }, (_, i) => i).filter((m) => m <= start.minute);
};

// ─── 时间变更联动（含间隔校验）──────────────────────────────
const onStartTimeChange = (task: TaskNode) => {
    clearTaskTimeError(task._key, "start");
    if (!task.end_time || task.scene === SCENE_VIDEO_PUBLISH) return;
    if (timeToMinutes(task.start_time) >= timeToMinutes(task.end_time)) {
        task.end_time = "";
        clearTaskTimeError(task._key, "end");
        return;
    }
    // 间隔校验
    if (!checkDuration(task)) {
        setTaskTimeError(task._key, "start", `与结束时间间隔不得少于 ${MIN_DURATION_MINUTES} 分钟`);
    } else {
        clearTaskTimeError(task._key, "end");
    }
};

const onEndTimeChange = (task: TaskNode) => {
    clearTaskTimeError(task._key, "end");
    if (!task.start_time) return;
    if (timeToMinutes(task.end_time) <= timeToMinutes(task.start_time)) {
        task.start_time = "";
        clearTaskTimeError(task._key, "start");
        return;
    }
    // 间隔校验
    if (!checkDuration(task)) {
        setTaskTimeError(task._key, "end", `与开始时间间隔不得少于 ${MIN_DURATION_MINUTES} 分钟`);
    } else {
        clearTaskTimeError(task._key, "start");
    }
};

// ─── 冲突检测 ────────────────────────────────────────────────
const conflictKeys = computed<Set<number>>(() => {
    const result = new Set<number>();
    const tasks = formData.schedule;
    for (let i = 0; i < tasks.length; i++) {
        const aStart = timeToMinutes(tasks[i].start_time);
        const aEnd = timeToMinutes(getEndTime(tasks[i]));
        if (aStart < 0 || aEnd < 0 || aEnd <= aStart) continue;
        for (let j = i + 1; j < tasks.length; j++) {
            const bStart = timeToMinutes(tasks[j].start_time);
            const bEnd = timeToMinutes(getEndTime(tasks[j]));
            if (bStart < 0 || bEnd < 0 || bEnd <= bStart) continue;
            if (aStart < bEnd && aEnd > bStart) {
                result.add(tasks[i]._key);
                result.add(tasks[j]._key);
            }
        }
    }
    return result;
});

// ─── 抽屉开关 ────────────────────────────────────────────────
const open = async (type: "add" | "edit") => {
    resetForm();
    mode.value = type;
    fetchCategories("");
    // 先拉任务类型开关，再展示抽屉，避免未过滤的完整列表闪现
    await fetchSceneAllowConfig();
    visible.value = true;
};

const closeDrawer = () => {
    visible.value = false;
    emit("close");
};

// ─── 表单重置 ────────────────────────────────────────────────
const resetForm = () => {
    Object.assign(formData, {
        id: "",
        name: "",
        category_id: null,
        operation_preference: 1,
        status: 1,
        description: "",
        detail_content: "",
        schedule: [],
    });
    Object.keys(errors).forEach((k) => delete errors[k]);
    Object.keys(taskTimeErrors).forEach((k) => delete taskTimeErrors[Number(k)]);
    activeDropdownKey.value = null;
    categoryOptions.value = [];
};

// ─── 回显（编辑时调用） ──────────────────────────────────────
const setFormData = (data: any) => {
    resetForm();
    formData.id = data.id ?? "";
    formData.name = data.name ?? "";
    formData.category_id = data.category_id ?? null;
    formData.operation_preference = data.operation_preference ?? 1;
    formData.status = data.status ?? 1;
    formData.description = data.description ?? "";
    formData.detail_content = data.detail_content ?? "";

    if (Array.isArray(data.schedule)) {
        formData.schedule = data.schedule.map((node: any) => {
            const scene = node.scene ?? DEFAULT_SCENE;
            const sortedPlatforms: number[] = filterOpenAccountTypes(
                scene,
                [...(node.platform ?? [])]
                    .sort((a: any, b: any) => a.order - b.order)
                    .map((p: any) => Number(p.account_type)),
            );
            const task: TaskNode = {
                _key: Date.now() + Math.random(),
                scene,
                account_types: sortedPlatforms,
                start_time: normalizeTimeStr(node.start_time ?? ""),
                end_time: normalizeTimeStr(node.end_time ?? ""),
            };
            syncVideoPublishEndTime(task);
            return task;
        });
    }
};

const normalizeTimeStr = (t: string): string => {
    if (!t) return "";
    const match = t.match(/^(\d{1,2}):(\d{2})/);
    if (!match) return "";
    const h = String(parseInt(match[1], 10)).padStart(2, "0");
    const m = String(parseInt(match[2], 10)).padStart(2, "0");
    return `${h}:${m}`;
};

// ─── 任务节点操作 ────────────────────────────────────────────
const addTaskNode = () => {
    if (!sceneConfigLoaded.value) {
        feedback.msgWarning("任务类型配置加载中，请稍后再试");
        return;
    }
    const scene = getDefaultScene();
    if (scene === null) {
        feedback.msgWarning("暂无可添加的任务类型，请先在「任务类型」中开启允许添加");
        return;
    }
    const defaultType = getAvailablePlatforms(scene)[0]?.type ?? DOYIN_TYPE;
    const startTime = findFreeStartTime(MINUTES_PER_PLATFORM);
    formData.schedule.push({
        _key: Date.now(),
        scene,
        account_types: [defaultType],
        start_time: startTime,
        end_time: "",
    });
    delete errors.schedule;
};

const removeTaskNode = (index: number) => {
    const key = formData.schedule[index]._key;
    formData.schedule.splice(index, 1);
    clearTaskTimeError(key);
};

const onTaskTypeChange = (task: TaskNode) => {
    const platforms = getAvailablePlatforms(task.scene);
    task.account_types = platforms.length >= 1 ? [platforms[0].type] : [];
    if (task.scene !== SCENE_VIDEO_PUBLISH) task.end_time = "";
    clearTaskTimeError(task._key);
    activeDropdownKey.value = null;
};

// ─── 平台多选操作 ────────────────────────────────────────────
const toggleDropdown = (key: number) => {
    activeDropdownKey.value = activeDropdownKey.value === key ? null : key;
};

const closeAllDropdowns = () => {
    activeDropdownKey.value = null;
};

const togglePlatform = (task: TaskNode, type: number) => {
    const idx = task.account_types.indexOf(type);
    idx >= 0 ? task.account_types.splice(idx, 1) : task.account_types.push(type);
};

const removePlatform = (task: TaskNode, type: number) => {
    task.account_types = task.account_types.filter((t) => t !== type);
};

// ─── 校验 ────────────────────────────────────────────────────
const validate = (): boolean => {
    Object.keys(errors).forEach((k) => delete errors[k]);
    // 清除所有行内时间错误后重新检测
    Object.keys(taskTimeErrors).forEach((k) => delete taskTimeErrors[Number(k)]);
    let valid = true;

    if (!formData.name.trim()) {
        errors.name = "请输入模板名称";
        valid = false;
    }
    if (!formData.category_id) {
        errors.category_id = "请选择一级分类";
        valid = false;
    }
    if (formData.schedule.length === 0) {
        errors.schedule = "请至少添加一个任务节点";
        valid = false;
    }

    const inForbiddenZone = formData.schedule.find(
        (t) => t.start_time && timeToMinutes(t.start_time) < FORBIDDEN_ZONE_END,
    );
    if (inForbiddenZone) {
        errors.schedule = "开始时间不可设置在 00:00 - 06:00 固定创作时间段内";
        valid = false;
    }

    const missingEnd = formData.schedule.find((t) => t.scene !== SCENE_VIDEO_PUBLISH && !t.end_time);
    if (missingEnd) {
        errors.schedule = "请完整填写所有节点的结束时间";
        valid = false;
    }

    const timeOrderError = formData.schedule.find((t) => {
        if (t.scene === SCENE_VIDEO_PUBLISH) return false;
        if (!t.start_time || !t.end_time) return false;
        return timeToMinutes(t.start_time) >= timeToMinutes(t.end_time);
    });
    if (timeOrderError) {
        errors.schedule = "存在开始时间不早于结束时间的节点，请检查后重试";
        valid = false;
    }

    // ✅ 间隔不足 5 分钟校验（同时写入行内错误）
    formData.schedule.forEach((t) => {
        if (!checkDuration(t)) {
            setTaskTimeError(t._key, "end", `与开始时间间隔不得少于 ${MIN_DURATION_MINUTES} 分钟`);
            errors.schedule = `存在时间间隔不足 ${MIN_DURATION_MINUTES} 分钟的节点，请调整后重试`;
            valid = false;
        }
    });

    if (conflictKeys.value.size > 0) {
        errors.schedule = "存在时间冲突的任务节点（已高亮标记），请调整后重试";
        valid = false;
    }

    return valid;
};

// ─── 构建提交参数 ────────────────────────────────────────────
const buildPayload = () => ({
    ...(mode.value === "edit" ? { id: formData.id } : {}),
    name: formData.name,
    category_id: formData.category_id,
    operation_preference: formData.operation_preference,
    status: formData.status,
    description: formData.description,
    detail_content: formData.detail_content,
    schedule: formData.schedule.map((t) => {
        const accountTypes = filterOpenAccountTypes(t.scene, t.account_types);
        const task = { ...t, account_types: accountTypes };
        return {
            scene: task.scene,
            start_time: task.start_time,
            end_time: getEndTime(task),
            platform: accountTypes.map((account_type, idx) => ({
                order: idx + 1,
                account_type,
            })),
        };
    }),
});

// ─── 提交 ────────────────────────────────────────────────────
const submit = async () => {
    if (!validate()) return Promise.reject("校验未通过");
    const payload = buildPayload();
    mode.value === "add" ? await addTaskTemplate(payload) : await editTaskTemplate(payload);
    closeDrawer();
    emit("success");
};

const { lockFn, isLock } = useLockFn(submit);

const getDetail = async (id: any) => {
    const data = await getTaskTemplateDetail({ id });
    setFormData(data);
};

defineExpose({ open, getDetail });
</script>

<style scoped>
.err-tip {
    @apply mt-1 text-xs text-[#ef4444];
}
</style>
