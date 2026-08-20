import { getSameCityInterceptKeywordHistory } from "@/api/device";
import { KeyEditTarget, MUTEX_ACTION_LIST } from "./types";
import type { PublishFormData } from "./types";

// ─────────────────────────────────────────────────────────────────
// 通用关键词列表 Hook
// 管理：添加 / 单条删除 / 一键清空 / 展开折叠
// ─────────────────────────────────────────────────────────────────
const KEYWORD_COLLAPSE_THRESHOLD = 5;

interface UseKeywordListOptions {
    /** 一键删除二次确认文案 */
    clearConfirmText: string;
    /** 词已存在提示文案 */
    duplicateTip: string;
}

function useKeywordList(getList: () => string[], setList: (val: string[]) => void, options: UseKeywordListOptions) {
    const input = ref("");
    const expanded = ref(false);

    const visible = computed(() => (expanded.value ? getList() : getList().slice(0, KEYWORD_COLLAPSE_THRESHOLD)));

    const overflow = computed(() => getList().length > KEYWORD_COLLAPSE_THRESHOLD);

    const toggle = () => {
        expanded.value = !expanded.value;
    };

    /** 输入框快捷添加 */
    const add = () => {
        const val = input.value.trim();
        if (!val) return;
        const list = getList();
        if (list.includes(val)) {
            uni.$u.toast(options.duplicateTip);
            return;
        }
        list.push(val);
        input.value = "";
    };

    /** 删除单条（直接操作原数组） */
    const remove = (idx: number) => {
        getList().splice(idx, 1);
    };

    /** 一键清空（带二次确认） */
    const clearAll = () => {
        uni.showModal({
            title: "提示",
            content: options.clearConfirmText,
            success: (res) => {
                if (res.confirm) {
                    setList([]);
                    expanded.value = false;
                }
            },
        });
    };

    return { input, expanded, visible, overflow, toggle, add, remove, clearAll };
}

// ─────────────────────────────────────────────────────────────────
// useSourceStep
// ─────────────────────────────────────────────────────────────────
export function useSourceStep(formData: PublishFormData) {
    const choosePersonRef = shallowRef();

    // ── 互动动作 ──────────────────────────────────────────────────
    const hasMutexSelected = computed(() => MUTEX_ACTION_LIST.some((a) => formData.marker_method.includes(a.key)));

    const toggleFreeAction = (key: number) => {
        const idx = formData.marker_method.indexOf(key);
        if (idx === -1) formData.marker_method.push(key);
        else formData.marker_method.splice(idx, 1);
    };

    const toggleMutexAction = (key: number) => {
        const idx = formData.marker_method.indexOf(key);
        if (idx !== -1) {
            formData.marker_method.splice(idx, 1);
        } else {
            MUTEX_ACTION_LIST.forEach(({ key: k }) => {
                const i = formData.marker_method.indexOf(k);
                if (i !== -1) formData.marker_method.splice(i, 1);
            });
            formData.marker_method.push(key);
        }
    };

    // ── 包含词（评论必须包含） ────────────────────────────────────
    const {
        input: includeNameInput,
        expanded: includeFilterExpanded,
        visible: visibleIncludeFilter,
        overflow: includeFilterOverflow,
        toggle: toggleIncludeFilter,
        add: handleAddIncludeName,
        remove: removeIncludeFilter,
        clearAll: handleClearAllIncludeFilter,
    } = useKeywordList(
        () => formData.include_filter,
        (val) => (formData.include_filter = val),
        {
            clearConfirmText: "确定要删除所有评论包含关键词吗？",
            duplicateTip: "该评论包含关键词已存在",
        },
    );

    // ── 昵称排除词 ────────────────────────────────────────────────
    const {
        input: excludeNameInput,
        expanded: nicknameFilterExpanded,
        visible: visibleNicknameFilter,
        overflow: nicknameFilterOverflow,
        toggle: toggleNicknameFilter,
        add: handleAddExcludeName,
        remove: removeNicknameFilter,
        clearAll: handleClearAllNicknameFilter,
    } = useKeywordList(
        () => formData.nickname_filter,
        (val) => (formData.nickname_filter = val),
        {
            clearConfirmText: "确定要删除所有昵称排除词吗？",
            duplicateTip: "该昵称词已存在",
        },
    );

    // ── 昵称排除词弹窗编辑 ────────────────────────────────────────
    const keywordsEditShow = ref(false);
    const keywordsEditTarget = ref<KeyEditTarget>(KeyEditTarget.Keywords);
    const keywordsEditIndex = ref(-1);
    const keywordsEditRef = ref<any>();
    const titleMap: Record<KeyEditTarget, string> = {
        [KeyEditTarget.Keywords]: "评论必须包含以下关键词",
        [KeyEditTarget.NicknameFilter]: "对方昵称不包含（防误触）",
    };

    const keywordsEditTitle = computed(() => titleMap[keywordsEditTarget.value]);

    const openKeywordsEdit = async (idx: number, target: KeyEditTarget) => {
        keywordsEditTarget.value = target;
        keywordsEditIndex.value = idx;
        keywordsEditShow.value = true;
        await nextTick();
        const currentVal = formData[keywordsEditTarget.value][idx];
        keywordsEditRef.value?.setFormData(currentVal ?? "");
    };

    const handleKeywordsConfirm = (value: string) => {
        const val = value.trim();
        if (!val) return;
        const list = formData[keywordsEditTarget.value];
        if (keywordsEditIndex.value === -1) {
            if (!list.includes(val)) {
                list.push(val);
            } else {
                uni.$u.toast("该词已存在");
                return;
            }
        } else {
            list[keywordsEditIndex.value] = val;
        }
        keywordsEditShow.value = false;
    };

    // ── IP 人设 ───────────────────────────────────────────────────
    const showChoosePersonPopup = ref(false);
    const personValue = ref<any>({});

    const handleSelectPerson = async () => {
        showChoosePersonPopup.value = true;
        await nextTick();
        choosePersonRef.value?.setChooseLists([{ id: personValue.value.id }]);
    };

    const handleChoosePersonConfirm = (data: any) => {
        personValue.value = data;
        formData.persona_id = data.id;
    };

    const clearIpPerson = () => {
        formData.persona_id = "";
        personValue.value = {};
    };

    // ── 自定义距离 ────────────────────────────────────────────────
    const customDistanceInput = ref<number | null>(5);
    const customDistanceError = ref<string>("");
    const isCustomDistance = ref(false);

    const handleSelectDistance = (value: number): void => {
        formData.radius = value;
        isCustomDistance.value = false;
        customDistanceError.value = "";
    };

    const handleSelectCustomDistance = (): void => {
        customDistanceError.value = "";
        isCustomDistance.value = true;
    };

    const handleCustomDistanceBlur = (): void => {
        const raw = customDistanceInput.value?.toString().trim();
        if (raw === "") {
            customDistanceError.value = "";
            formData.radius = 0;
            return;
        }
        customDistanceError.value = "";
        formData.radius = Number(raw);
    };

    // ── 初始化历史记录 ────────────────────────────────────────────
    const getKeywordHistory = async () => {
        const { filter, nickname_filter } = await getSameCityInterceptKeywordHistory();
        formData.include_filter = filter;
        formData.nickname_filter = nickname_filter;
    };

    onMounted(() => {
        getKeywordHistory();
    });

    // ── 导出 ──────────────────────────────────────────────────────
    return {
        // 互动动作
        hasMutexSelected,
        toggleFreeAction,
        toggleMutexAction,
        // 包含词
        includeNameInput,
        handleAddIncludeName,
        removeIncludeFilter,
        includeFilterExpanded,
        visibleIncludeFilter,
        includeFilterOverflow,
        toggleIncludeFilter,
        handleClearAllIncludeFilter,
        // 昵称排除词
        excludeNameInput,
        handleAddExcludeName,
        removeNicknameFilter,
        nicknameFilterExpanded,
        visibleNicknameFilter,
        nicknameFilterOverflow,
        toggleNicknameFilter,
        handleClearAllNicknameFilter,
        // 昵称排除词弹窗编辑
        keywordsEditShow,
        keywordsEditTitle,
        keywordsEditRef,
        openKeywordsEdit,
        handleKeywordsConfirm,
        // IP 人设
        choosePersonRef,
        showChoosePersonPopup,
        personValue,
        handleSelectPerson,
        handleChoosePersonConfirm,
        clearIpPerson,
        // 自定义距离
        isCustomDistance,
        customDistanceInput,
        customDistanceError,
        handleSelectDistance,
        handleSelectCustomDistance,
        handleCustomDistanceBlur,
    };
}
