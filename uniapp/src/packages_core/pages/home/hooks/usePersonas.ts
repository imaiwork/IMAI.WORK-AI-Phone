import { ref, type Ref } from "vue";
import config from "@/config";
import { getPersonList } from "@/api/person";
import { PersonTypeEnum, PersonTypeMap } from "@/enums/appEnums";

// ===== 类型 =====
export type HomePersona = {
    id: string | number;
    key: string;
    name: string;
    tag: string;
    accent: string;
    tagBg: string;
    avatarBg: string;
    statusColor: string;
    deviceCount: number;
    deviceText: string;
    avatar: string;
    raw: Record<string, any>;
};

export type PersonaStyle = Pick<HomePersona, "accent" | "tagBg" | "avatarBg">;

// ===== 常量 =====
export const PERSONA_THEME_COLOR = "#0065FB";
export const PERSONA_THEME_BG = "#E6EFFF";
export const PERSONA_NORMAL_COLOR = "#6B7280";
export const PERSONA_NORMAL_BG = "#F3F4F6";
const defaultPersonaAvatar = `${config.baseUrl}static/images/mp/person_default.png`;

const defaultPersonaStyle: PersonaStyle = {
    accent: PERSONA_THEME_COLOR,
    tagBg: PERSONA_THEME_BG,
    avatarBg: "linear-gradient(135deg,#eef2ff,#e0f2fe)",
};

const personaTypeStyleMap: Record<number, PersonaStyle> = {
    [PersonTypeEnum.PERSONAL_IP]: defaultPersonaStyle,
    [PersonTypeEnum.BUSINESS_SERVICE]: defaultPersonaStyle,
    [PersonTypeEnum.LOCAL_BUSINESS]: defaultPersonaStyle,
};

const personaStylePresets: PersonaStyle[] = [defaultPersonaStyle];

// ===== 归一化辅助 =====
const getPersonaDeviceCount = (item: Record<string, any>) => {
    const count = Number(item.device_num ?? item.device_count ?? item.devices?.length ?? 0);
    return Number.isFinite(count) ? count : 0;
};

const getPersonaStatusColor = (item: Record<string, any>, deviceCount: number) => {
    if (Number(item.is_configured) !== 1) return "#d1d5db";
    return deviceCount > 0 ? "#4ade80" : "#facc15";
};

const getPersonaTypeName = (type: number) => {
    return PersonTypeMap[type as keyof typeof PersonTypeMap] || "AI员工";
};

const normalizePersona = (item: Record<string, any>, index: number): HomePersona => {
    const id = item.id ?? item.persona_id ?? item.ai_persona_id ?? "";
    const personaType = Number(item.persona_type) || 0;
    const style = personaTypeStyleMap[personaType] || personaStylePresets[index % personaStylePresets.length];
    const deviceCount = getPersonaDeviceCount(item);

    return {
        id,
        key: String(id || `${item.persona_name || "persona"}-${index}`),
        name: item.persona_name || item.name || "未命名AI员工",
        tag: getPersonaTypeName(personaType),
        accent: style.accent,
        tagBg: style.tagBg,
        avatarBg: style.avatarBg,
        statusColor: getPersonaStatusColor(item, deviceCount),
        deviceCount,
        deviceText: deviceCount > 0 ? `正在控制 ${deviceCount} 部手机` : "暂未关联手机",
        avatar: item.avatar_url || item.avatar || defaultPersonaAvatar,
        raw: item,
    };
};

const normalizePersonaResponse = (data: any) => {
    const lists = Array.isArray(data) ? data : Array.isArray(data?.lists) ? data.lists : [];
    const count = Number(data?.count ?? data?.total ?? lists.length);

    return {
        lists: lists.map((item: Record<string, any>, index: number) => normalizePersona(item, index)),
        count: Number.isFinite(count) ? count : lists.length,
    };
};

// ===== 演示数据（未登录时使用） =====
const buildDemoPersona = (
    overrides: Partial<HomePersona> & Pick<HomePersona, "id" | "key" | "name" | "tag" | "deviceCount">,
): HomePersona => ({
    accent: PERSONA_THEME_COLOR,
    tagBg: PERSONA_THEME_BG,
    avatarBg: "linear-gradient(135deg,#eef2ff,#e0f2fe)",
    statusColor: overrides.deviceCount > 0 ? "#4ade80" : "#facc15",
    deviceText: overrides.deviceCount > 0 ? `正在控制 ${overrides.deviceCount} 部手机` : "暂未关联手机",
    avatar: defaultPersonaAvatar,
    raw: {},
    ...overrides,
});

const DEMO_PERSONAS: HomePersona[] = [
    buildDemoPersona({
        id: "demo-xiaoya",
        key: "xiaoya",
        name: "美妆顾问-小雅",
        tag: "本地商家",
        deviceCount: 2,
    }),
    buildDemoPersona({
        id: "demo-opc",
        key: "opc",
        name: "私域运营-小敏",
        tag: "私域运营",
        deviceCount: 1,
    }),
];

export const isDemoPersona = (persona?: HomePersona | null) =>
    !!persona && typeof persona.id === "string" && persona.id.startsWith("demo-");

export interface UsePersonasDeps {
    /** 用户登录态（getter；未登录则不发请求并喂演示数据） */
    isLogin: Ref<boolean>;
    /** 切换人设后的副作用（例：清空 workflow 展开态、触发 panel 刷新） */
    onActiveChange?: () => void;
}

// 首页人设列表：状态 + 查询 + 切换；与 workflow 面板之间通过 activePersona 共享
export function usePersonas(deps: UsePersonasDeps) {
    const { isLogin, onActiveChange } = deps;

    const personas = ref<HomePersona[]>([]);
    const personaTotal = ref(0);
    const personaLoading = ref(false);
    const personaError = ref(false);
    const activePersona = ref("");

    let personaRequestId = 0;

    const queryPersonaList = async () => {
        const requestId = ++personaRequestId;
        personaLoading.value = true;
        personaError.value = false;

        // 未登录：直接喂演示数据，不发请求、不显示登录引导
        if (!isLogin.value) {
            personas.value = DEMO_PERSONAS;
            personaTotal.value = DEMO_PERSONAS.length;
            if (!DEMO_PERSONAS.some((item) => item.key === activePersona.value)) {
                activePersona.value = DEMO_PERSONAS[0].key;
                onActiveChange?.();
            }
            personaLoading.value = false;
            return;
        }

        try {
            const data = await getPersonList({ page_no: 1, page_size: 20 });
            if (requestId !== personaRequestId) return;

            const { lists, count } = normalizePersonaResponse(data);
            personas.value = lists;
            personaTotal.value = count;
            if (!lists.length) {
                activePersona.value = "";
                onActiveChange?.();
                return;
            }
            if (!lists.some((item: HomePersona) => item.key === activePersona.value)) {
                activePersona.value = lists[0].key;
                onActiveChange?.();
            }
        } catch (error) {
            personas.value = [];
            personaTotal.value = 0;
            activePersona.value = "";
            personaError.value = true;
            onActiveChange?.();
        } finally {
            personaLoading.value = false;
        }
    };

    return {
        personas,
        personaTotal,
        personaLoading,
        personaError,
        activePersona,
        queryPersonaList,
    };
}
