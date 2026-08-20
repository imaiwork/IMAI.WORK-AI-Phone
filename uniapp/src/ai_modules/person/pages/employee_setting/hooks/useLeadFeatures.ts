import { ref, reactive, type InjectionKey, type Ref } from "vue";
import MapPinTealIcon from "@/ai_modules/person/static/icons/employee/map-pin-teal.svg";
import ScissorsBlueIcon from "@/ai_modules/person/static/icons/employee/scissors-blue.svg";
import ShoppingBagPinkIcon from "@/ai_modules/person/static/icons/employee/shopping-bag-pink.svg";
import VideoOrangeIcon from "@/ai_modules/person/static/icons/employee/video-orange.svg";

export type LeadFeatureKey = "wxv" | "jl" | "tg" | "tc";
export type Gender = "不限" | "男" | "女";
export type DualAction = "" | "comment" | "dm";
export type RiskConfigKey =
    | "messageNumber"
    | "cityCommentNumber"
    | "videoMessageNumber"
    | "cityMessageNumber"
    | "grouponMessageNumber";

export interface LeadFeature {
    key: LeadFeatureKey;
    title: string;
    desc: string;
    icon: string;
    iconBg: string;
}

export interface RiskConfig {
    contentPublishTime: number;
    commentPublishTime: number;
    // 滑块绑定值统一用字符串，避免 u-slider 在小程序端的数值类型问题
    messageNumber: string;
    cityCommentNumber: string;
    videoMessageNumber: string;
    cityMessageNumber: string;
    grouponMessageNumber: string;
    replyNumber: number;
}

export interface KeywordEditOptions {
    title: string;
    label: string;
    placeholder: string;
    tip: string;
    maxlength?: number;
    maxItems?: number;
    allowMultiple?: boolean;
}

export interface UseLeadFeaturesDeps {
    /** 共享的关键词编辑弹窗入口，由父层提供（避免 hook 与 popup 实现耦合） */
    openKeywordEditPopup: (list: Ref<string[]> | string[], index: number, options: KeywordEditOptions) => void;
}

// 业务线索/截流/团购/同城/风控相关的状态与操作；与 popup 实现解耦，由调用方注入入口
export function useLeadFeatures(deps: UseLeadFeaturesDeps) {
    const { openKeywordEditPopup } = deps;

    // ===== 常量 =====
    const LEAD_KEYWORD_INPUT_MAXLENGTH = 120;
    const LEAD_KEYWORD_BATCH_TIP = "多个关键词可用 ; 分隔，一次保存多个。";
    const buildLeadKeywordEditOptions = (options: KeywordEditOptions): KeywordEditOptions => ({
        ...options,
        allowMultiple: true,
        maxlength: options.maxlength || LEAD_KEYWORD_INPUT_MAXLENGTH,
        tip: `${options.tip} ${LEAD_KEYWORD_BATCH_TIP}`,
    });

    const leadFeatureList: LeadFeature[] = [
        {
            key: "wxv",
            title: "视频号获客",
            desc: "从视频号评论/简介中提取联系方式",
            icon: VideoOrangeIcon,
            iconBg: "#FFF7ED",
        },
        {
            key: "jl",
            title: "截流设置",
            desc: "拦截搜索目标关键词的用户",
            icon: ScissorsBlueIcon,
            iconBg: "#EFF6FF",
        },
        {
            key: "tg",
            title: "团购截流",
            desc: "在同行团购下评论引流",
            icon: ShoppingBagPinkIcon,
            iconBg: "#FDF2F8",
        },
        {
            key: "tc",
            title: "同城截流",
            desc: "向同城客户主动精准触达",
            icon: MapPinTealIcon,
            iconBg: "#F0FDFA",
        },
    ];

    const genderOptions: Gender[] = ["不限", "男", "女"];
    const distanceOptions = ["不限", "1km内", "3km内", "5km内", "10km内", "自定义"];
    const contentPublishTimeOptions = [
        { label: "不限", value: 0 },
        { label: "一天内", value: 1 },
        { label: "一周内", value: 7 },
        { label: "半年内", value: 180 },
    ];
    const commentPublishTimeOptions = [
        { label: "当天", value: 1 },
        { label: "2天内", value: 2 },
        { label: "3天内", value: 3 },
        { label: "4天内", value: 4 },
        { label: "5天内", value: 5 },
        { label: "6天内", value: 6 },
        { label: "7天内", value: 7 },
        { label: "不限制", value: -1 },
    ];
    const riskSliderList: Array<{ key: RiskConfigKey; label: string }> = [
        { key: "messageNumber", label: "截流主动私信每天最大互动人数" },
        { key: "cityCommentNumber", label: "同城触达评论每天最大互动人数" },
        { key: "videoMessageNumber", label: "每日视频截流人数上限" },
        { key: "cityMessageNumber", label: "每日同城截流人数上限" },
        { key: "grouponMessageNumber", label: "每日团购截流人数上限" },
    ];

    // ===== 状态 =====
    const leadFeatureStatus = reactive<Record<LeadFeatureKey, boolean>>({
        wxv: true,
        jl: true,
        tg: false,
        tc: true,
    });
    // 多开：默认全部展开，可各自独立收起
    const openLeadFeature = ref<LeadFeatureKey[]>(leadFeatureList.map((feature) => feature.key));
    const showLeadRiskPanel = ref(true);

    // wxv
    const wxvKeywords = ref(["创业者", "企业主", "工作室"]);
    const wxvMoreWords = ref(["老板", "合伙人", "副业", "代理", "加盟", "招募"]);
    const showWxvMore = ref(false);
    const wxvLimit = ref(10);

    // jl
    const jlSearchWords = ref(["2024AI使用教程", "AI落地应用场景", "普通人怎么用AI提效"]);
    const jlSearchMoreWords = ref(["AI副业", "AI赚钱", "AI工具推荐", "AI变现"]);
    const jlCommentWords = ref(["怎么落地", "不会用", "求教程"]);
    const jlCommentMoreWords = ref(["怎么学", "在哪学", "想入门", "求链接", "怎么买", "推荐课程"]);
    const showJlSearchMore = ref(false);
    const showJlCommentMore = ref(false);
    const jlLimit = ref(10);

    // tg
    const tgForm = reactive({
        type: "",
        execNum: "15",
        commentStart: 1,
        watchSeconds: 10,
        touchInterval: 10,
        gender: "不限" as Gender,
        ip: "",
        region: "",
    });
    const tgCommentInput = ref("");
    const tgKeywords = ref<string[]>([]);
    const tgActions = reactive({
        like: false,
        follow: false,
        dual: "" as DualAction,
    });
    const tgExcludeInput = ref("");
    const tgExcludeWords = ref<string[]>([]);

    // tc
    const tcForm = reactive({
        watchSeconds: 10,
        touchInterval: 10,
        distance: "不限",
        customDistance: "",
        gender: "不限" as Gender,
        ageMin: "18",
        ageMax: "60",
        likeMin: "0",
        commentMax: "0",
        fansMin: "0",
        fansMax: "0",
        followMin: "0",
        followMax: "0",
    });
    const tcActions = reactive({
        like: true,
        follow: true,
        dual: "" as DualAction,
    });
    const tcExcludeInput = ref("");
    const tcExcludeWords = ref<string[]>([]);

    // 风控
    const riskConfig = reactive<RiskConfig>({
        contentPublishTime: 0,
        commentPublishTime: -1,
        messageNumber: "15",
        cityCommentNumber: "15",
        videoMessageNumber: "15",
        cityMessageNumber: "15",
        grouponMessageNumber: "15",
        replyNumber: 2,
    });

    // ===== 通用工具 =====
    const addInputKeyword = (list: string[], input: Ref<string>) => {
        const value = input.value.trim();
        if (!value) return;
        if (!list.includes(value)) list.push(value);
        input.value = "";
    };

    const removeKeyword = (list: string[], word: string) => {
        const index = list.indexOf(word);
        if (index >= 0) list.splice(index, 1);
    };

    // ===== 操作 =====
    const toggleLeadFeature = (key: LeadFeatureKey) => {
        const index = openLeadFeature.value.indexOf(key);
        if (index >= 0) openLeadFeature.value.splice(index, 1);
        else openLeadFeature.value.push(key);
    };

    const changeWxvLimit = (delta: number) => {
        wxvLimit.value = Math.max(1, wxvLimit.value + delta);
    };

    const changeJlLimit = (delta: number) => {
        jlLimit.value = Math.max(0, jlLimit.value + delta);
    };

    const selectDistance = (distance: string) => {
        tcForm.distance = distance;
        if (distance !== "自定义") {
            tcForm.customDistance = "";
        }
    };

    const customDistanceInput = (event: any) => {
        const value = String(event.detail.value || "")
            .replace(/\D/g, "")
            .replace(/^0+/, "");
        tcForm.customDistance = value;
        return value;
    };

    // ===== 弹窗打开（注入式 openKeywordEditPopup）=====
    const openWxvKeywordPopup = (index = -1) =>
        openKeywordEditPopup(wxvKeywords, index, buildLeadKeywordEditOptions({
            title: index >= 0 ? "编辑视频号线索词" : "添加视频号线索词",
            label: "线索关键词",
            placeholder: "如：创业者、企业主",
            tip: "保存后，AI 会按这个线索词从视频号评论和简介中提取联系方式。",
        }));

    const openWxvMoreKeywordPopup = (index = -1) =>
        openKeywordEditPopup(wxvMoreWords, index, buildLeadKeywordEditOptions({
            title: "编辑更多线索词",
            label: "线索关键词",
            placeholder: "如：老板、合伙人",
            tip: "保存后会加入展开区的视频号线索词列表。",
        }));

    const openJlSearchWordPopup = (index = -1) =>
        openKeywordEditPopup(jlSearchWords, index, buildLeadKeywordEditOptions({
            title: index >= 0 ? "编辑视频搜索词" : "添加视频搜索词",
            label: "视频搜索词",
            placeholder: "如：AI落地应用场景",
            tip: "用于搜索同行视频，辅助筛选目标评论用户。",
        }));

    const openJlSearchMoreWordPopup = (index = -1) =>
        openKeywordEditPopup(jlSearchMoreWords, index, buildLeadKeywordEditOptions({
            title: "编辑更多搜索词",
            label: "视频搜索词",
            placeholder: "如：AI副业",
            tip: "保存后会加入展开区的搜索词列表。",
        }));

    const openJlCommentWordPopup = (index = -1) =>
        openKeywordEditPopup(jlCommentWords, index, buildLeadKeywordEditOptions({
            title: index >= 0 ? "编辑评论匹配词" : "添加评论匹配词",
            label: "评论匹配词",
            placeholder: "如：求教程、怎么买",
            tip: "评论包含该词时，会被识别为潜在意向用户。",
        }));

    const openJlCommentMoreWordPopup = (index = -1) =>
        openKeywordEditPopup(jlCommentMoreWords, index, buildLeadKeywordEditOptions({
            title: "编辑更多匹配词",
            label: "评论匹配词",
            placeholder: "如：在哪学",
            tip: "保存后会加入展开区的评论匹配词列表。",
        }));

    const openTgKeywordPopup = (index = -1) =>
        openKeywordEditPopup(tgKeywords, index, buildLeadKeywordEditOptions({
            title: index >= 0 ? "编辑团购关键词" : "添加团购关键词",
            label: "评论关键词",
            placeholder: "如：怎么买、划算吗",
            tip: "评论必须包含该关键词，才会进入团购截流任务。",
        }));

    const openTgExcludeWordPopup = (index = -1) =>
        openKeywordEditPopup(tgExcludeWords, index, buildLeadKeywordEditOptions({
            title: index >= 0 ? "编辑排除词" : "添加排除词",
            label: "排除词",
            placeholder: "如：同行、客服",
            tip: "昵称命中排除词时将跳过，避免误触。",
        }));

    const openTcExcludeWordPopup = (index = -1) =>
        openKeywordEditPopup(tcExcludeWords, index, buildLeadKeywordEditOptions({
            title: index >= 0 ? "编辑排除词" : "添加排除词",
            label: "排除词",
            placeholder: "如：店长、主播",
            tip: "昵称命中排除词时将跳过，避免误触。",
        }));

    const addTgKeyword = () => {
        if (!tgCommentInput.value.trim()) {
            openTgKeywordPopup();
            return;
        }
        addInputKeyword(tgKeywords.value, tgCommentInput);
    };

    const addTgExcludeWord = () => {
        if (!tgExcludeInput.value.trim()) {
            openTgExcludeWordPopup();
            return;
        }
        addInputKeyword(tgExcludeWords.value, tgExcludeInput);
    };

    const addTcExcludeWord = () => {
        if (!tcExcludeInput.value.trim()) {
            openTcExcludeWordPopup();
            return;
        }
        addInputKeyword(tcExcludeWords.value, tcExcludeInput);
    };

    return {
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
        // 工具
        addInputKeyword,
        removeKeyword,
        // 操作
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
    };
}

export type LeadFeaturesContext = ReturnType<typeof useLeadFeatures>;
export const LeadFeaturesKey: InjectionKey<LeadFeaturesContext> = Symbol("LeadFeatures");
