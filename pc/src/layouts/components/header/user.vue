<template>
    <div class="flex items-center">
        <ElDropdown trigger="click" @command="onTeamCommand" @visible-change="onTeamMenuOpen">
            <div
                :class="`px-3 h-10 cursor-pointer flex items-center justify-center rounded-full gap-x-[6px] hover:bg-[${getTheme.hoverBgColor}]`"
                :style="{ boxShadow: getTheme.shadow, color: getTheme.iconColor }">
                <span class="w-5 h-5 flex items-center justify-center rounded-full bg-primary">
                    <Icon name="local-icon-user" :size="12" color="#ffffff"></Icon>
                </span>
                <span class="font-medium max-w-[160px] truncate" :style="{ color: getTheme.textColor }">
                    {{ teamBtnLabel }}
                </span>
            </div>
            <template #dropdown>
                <ElDropdownMenu class="team-dropdown !min-w-[288px] !py-2">
                    <!-- 小标题 -->
                    <div
                        v-if="myTeams.length"
                        class="px-4 pb-1.5 text-[11px] font-semibold tracking-wide text-slate-400">
                        切换空间
                    </div>
                    <!-- 空态 -->
                    <div v-else class="px-4 py-3 text-center">
                        <div class="text-[13px] font-medium text-slate-600">还没有加入任何企业</div>
                        <div class="text-[11px] text-slate-400 mt-1">创建或加入一个企业，开启团队协作</div>
                    </div>

                    <!-- 个人空间(有企业时才需要切换) -->
                    <ElDropdownItem
                        v-if="myTeams.length"
                        command="switch:0"
                        :class="{ '!bg-primary/5': isPersonalCurrent }">
                        <div class="flex items-center gap-2.5 w-full py-1">
                            <span
                                class="w-7 h-7 rounded-lg grid place-items-center text-white shrink-0"
                                style="background: linear-gradient(135deg, #64748b, #94a3b8)">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="#fff"
                                    stroke-width="1.8"
                                    class="w-[16px] h-[16px]">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M20 21a8 8 0 10-16 0M12 11a4 4 0 100-8 4 4 0 000 8z" />
                                </svg>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-[13px] font-medium leading-tight">个人空间</span>
                                <span class="block text-[11px] text-slate-400 leading-tight"
                                    >个人算力 {{ personalTokens }}</span
                                >
                            </span>
                            <span
                                v-if="isPersonalCurrent"
                                class="text-[10px] text-primary font-semibold bg-primary/10 px-1.5 py-0.5 rounded-full shrink-0"
                                >当前</span
                            >
                        </div>
                    </ElDropdownItem>

                    <!-- 企业列表(过期:灰色不可点,算力仍展示便于知晓挂在何处) -->
                    <ElDropdownItem
                        v-for="t in myTeams"
                        :key="t.team_id"
                        :command="Number(t.expired) === 1 ? '' : 'switch:' + t.team_id"
                        :disabled="Number(t.expired) === 1"
                        :class="{
                            '!bg-primary/5': t.is_current === 1 && Number(t.expired) !== 1,
                            'is-team-expired': Number(t.expired) === 1,
                        }">
                        <div class="flex items-center gap-2.5 w-full py-1">
                            <span
                                class="w-7 h-7 rounded-lg grid place-items-center text-white text-xs font-bold shrink-0"
                                :style="
                                    Number(t.expired) === 1
                                        ? 'background: linear-gradient(135deg, #94a3b8, #cbd5e1)'
                                        : 'background: linear-gradient(135deg, #0065fb, #4f9dff)'
                                ">
                                {{ (t.name || "T").slice(0, 1) }}
                            </span>
                            <span class="flex-1 min-w-0">
                                <span
                                    class="block text-[13px] font-medium truncate leading-tight"
                                    :class="Number(t.expired) === 1 ? 'text-slate-400' : ''"
                                    >{{ t.name }}</span
                                >
                                <span
                                    class="block text-[11px] leading-tight"
                                    :class="Number(t.expired) === 1 ? 'text-slate-300' : 'text-slate-400'"
                                    >企业算力 {{ t.is_owner === 1 ? t.owner_tokens : t.team_tokens
                                    }}<template v-if="t.is_owner === 1"> · 我创建的</template
                                    ><template v-else-if="Number(t.expired) === 1"> · 已过期</template></span
                                >
                            </span>
                            <span
                                v-if="Number(t.expired) === 1"
                                class="text-[10px] text-slate-400 font-semibold bg-slate-100 px-1.5 py-0.5 rounded-full shrink-0"
                                >已过期</span
                            >
                            <span
                                v-else-if="t.is_current === 1"
                                class="text-[10px] text-primary font-semibold bg-[#0065fb]/10 px-1.5 py-0.5 rounded-full shrink-0"
                                >当前</span
                            >
                        </div>
                    </ElDropdownItem>

                    <!-- 操作区：创建 / 加入 -->
                    <ElDropdownItem v-if="!hasOwnedTeam" command="create" :divided="myTeams.length > 0">
                        <div class="flex items-center gap-2.5 py-1">
                            <span
                                class="w-7 h-7 rounded-lg grid place-items-center text-primary shrink-0"
                                style="background: rgba(0, 101, 251, 0.1)">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="w-[16px] h-[16px]">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3.5 21h17M5 21V4.5A1.5 1.5 0 016.5 3h7A1.5 1.5 0 0115 4.5V21M15 10h2.5A1.5 1.5 0 0119 11.5V21M8 7h4M8 11h4M8 15h4" />
                                </svg>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-[13px] font-medium text-primary leading-tight">创建企业</span>
                                <span class="block text-[11px] text-slate-400 leading-tight"
                                    >成为团队主，管理成员与品牌</span
                                >
                            </span>
                        </div>
                    </ElDropdownItem>
                    <ElDropdownItem command="join" :divided="hasOwnedTeam && myTeams.length > 0">
                        <div class="flex items-center gap-2.5 py-1">
                            <span
                                class="w-7 h-7 rounded-lg grid place-items-center bg-emerald-50 text-emerald-500 shrink-0">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    class="w-[16px] h-[16px]">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 19a4 4 0 00-4-4H7a4 4 0 00-4 4M9 11a3 3 0 100-6 3 3 0 000 6zM18 8v6M21 11h-6" />
                                </svg>
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-[13px] font-medium text-slate-700 leading-tight">加入团队</span>
                                <span class="block text-[11px] text-slate-400 leading-tight">输入邀请码加入企业</span>
                            </span>
                        </div>
                    </ElDropdownItem>
                </ElDropdownMenu>
            </template>
        </ElDropdown>
        <!-- 齿轮:进入当前企业设置(无任何企业时隐藏) -->
        <div
            v-if="myTeams.length > 0"
            class="w-9 h-9 ml-1 cursor-pointer rounded-full grid place-items-center transition-colors"
            :class="`hover:bg-[${getTheme.hoverBgColor}]`"
            :style="{ color: getTheme.iconColor }"
            title="企业设置"
            @click="openTeamConsole">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-[19px] h-[19px]">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M10.34 3.94c.42-1.75 2.9-1.75 3.32 0a1.72 1.72 0 002.57 1.06c1.54-.94 3.3.82 2.37 2.37a1.72 1.72 0 001.06 2.57c1.75.42 1.75 2.9 0 3.32a1.72 1.72 0 00-1.06 2.57c.94 1.54-.82 3.3-2.37 2.37a1.72 1.72 0 00-2.57 1.06c-.42 1.75-2.9 1.75-3.32 0a1.72 1.72 0 00-2.57-1.06c-1.54.94-3.3-.82-2.37-2.37a1.72 1.72 0 00-1.06-2.57c-1.75-.42-1.75-2.9 0-3.32a1.72 1.72 0 001.06-2.57c-.94-1.54.82-3.3 2.37-2.37.98.6 2.27.08 2.57-1.06z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
        </div>
        <div class="ml-2">
            <free-experience v-if="!isLogin" />
            <tokens-panel v-else />
        </div>
        <ElDivider direction="vertical" :style="{ borderColor: getTheme.lineColor }" />
        <div>
            <user-panel v-if="isLogin" @recharge="openDataPackage" />
            <div v-else class="flex items-center bg-primary rounded-full h-10 text-white overflow-hidden">
                <div
                    class="px-4 h-full flex items-center font-medium cursor-pointer hover:bg-primary-light-3 transition-colors"
                    @click="openLogin()">
                    登录
                </div>
                <ElDivider direction="vertical" class="!border-l-[#ffffff33] !mx-0 !h-3" />
                <div
                    class="px-4 h-full flex items-center font-medium cursor-pointer hover:bg-primary-light-3 transition-colors"
                    @click="openRegister()">
                    注册
                </div>
            </div>
        </div>

        <!-- 加入团队弹窗 -->
        <ElDialog
            v-model="joinVisible"
            width="420px"
            align-center
            append-to-body
            :show-close="false"
            class="team-join-dialog">
            <div class="px-1 pb-1">
                <button class="join-close" @click="joinVisible = false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
                <div class="text-center pt-3">
                    <div
                        class="w-16 h-16 mx-auto rounded-2xl grid place-items-center shadow-lg"
                        style="
                            background: linear-gradient(135deg, #10b981, #34d399);
                            box-shadow: 0 10px 24px -6px rgba(16, 185, 129, 0.5);
                        ">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" class="w-8 h-8">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 19a4 4 0 00-4-4H7a4 4 0 00-4 4M9 11a3 3 0 100-6 3 3 0 000 6zM18 8v6M21 11h-6" />
                        </svg>
                    </div>
                    <h3 class="text-[19px] font-[800] text-slate-900 mt-4">加入团队</h3>
                    <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                        输入团队主给你的邀请码<br />加入企业，共享算力 · 智能体 · 知识库
                    </p>
                </div>
                <ElInput
                    v-model="joinCode"
                    placeholder="请输入邀请码"
                    size="large"
                    maxlength="32"
                    class="join-input mt-5"
                    @keyup.enter="submitJoin">
                    <template #prefix>
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="#94a3b8"
                            stroke-width="1.7"
                            class="w-[18px] h-[18px]">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 7h3a2 2 0 012 2v6a2 2 0 01-2 2h-3M9 17H6a2 2 0 01-2-2V9a2 2 0 012-2h3M8 12h8" />
                        </svg>
                    </template>
                </ElInput>
                <div class="flex gap-3 mt-6">
                    <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="joinVisible = false"
                        >取消</ElButton
                    >
                    <ElButton
                        type="primary"
                        class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                        :loading="joinLoading"
                        @click="submitJoin">
                        加入团队
                    </ElButton>
                </div>
            </div>
        </ElDialog>

        <!-- 创建企业弹窗 -->
        <ElDialog
            v-model="createVisible"
            width="420px"
            align-center
            append-to-body
            :show-close="false"
            class="team-join-dialog">
            <div class="px-1 pb-1">
                <button class="join-close" @click="createVisible = false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
                <div class="text-center pt-3">
                    <div
                        class="w-16 h-16 mx-auto rounded-2xl grid place-items-center shadow-lg"
                        style="
                            background: linear-gradient(135deg, #0065fb, #4f9dff);
                            box-shadow: 0 10px 24px -6px rgba(0, 101, 251, 0.5);
                        ">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" class="w-8 h-8">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3.5 21h17M5 21V4.5A1.5 1.5 0 016.5 3h7A1.5 1.5 0 0115 4.5V21M15 10h2.5A1.5 1.5 0 0119 11.5V21M8 7h4M8 11h4M8 15h4" />
                        </svg>
                    </div>
                    <h3 class="text-[19px] font-[800] text-slate-900 mt-4">创建企业</h3>
                    <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                        创建你的企业空间<br />成为团队主，管理成员 · 品牌 · 算力
                    </p>
                </div>
                <ElInput
                    v-model="createName"
                    placeholder="请输入企业名称"
                    size="large"
                    maxlength="30"
                    show-word-limit
                    class="join-input mt-5"
                    @keyup.enter="submitCreate">
                    <template #prefix>
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="#94a3b8"
                            stroke-width="1.7"
                            class="w-[18px] h-[18px]">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3.5 21h17M5 21V4.5A1.5 1.5 0 016.5 3h7A1.5 1.5 0 0115 4.5V21M15 10h2.5A1.5 1.5 0 0119 11.5V21M8 7h4M8 11h4M8 15h4" />
                        </svg>
                    </template>
                </ElInput>
                <div class="flex gap-3 mt-6">
                    <ElButton class="!flex-1 !h-11 !rounded-xl !text-[15px]" @click="createVisible = false"
                        >取消</ElButton
                    >
                    <ElButton
                        type="primary"
                        class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                        :loading="createLoading"
                        @click="submitCreate">
                        创建企业
                    </ElButton>
                </div>
            </div>
        </ElDialog>

        <!-- 切换空间确认 -->
        <ElDialog
            v-model="switchVisible"
            width="440px"
            align-center
            append-to-body
            :show-close="false"
            class="team-join-dialog"
            @closed="onSwitchClosed">
            <div class="px-1 pb-1">
                <button class="join-close" :disabled="switchLoading" @click="switchVisible = false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="w-4 h-4">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
                <div class="text-center pt-3">
                    <div
                        class="w-16 h-16 mx-auto rounded-2xl grid place-items-center"
                        style="
                            background: linear-gradient(135deg, #0065fb, #4f9dff);
                            box-shadow: 0 10px 24px -6px rgba(0, 101, 251, 0.45);
                        ">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.8" class="w-8 h-8">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-9L21 12m0 0L16.5 16.5M21 12H7.5" />
                        </svg>
                    </div>
                    <h3 class="text-[19px] font-[800] text-slate-900 mt-4">切换空间</h3>
                    <p class="text-[13px] text-slate-400 mt-1.5 leading-relaxed">
                        确认切换到「<span class="font-semibold text-slate-600">{{ switchTargetName }}</span>」？
                    </p>
                </div>

                <div class="switch-route mt-5">
                    <div class="switch-chip">
                        <span class="switch-chip__dot is-from" />
                        <span class="switch-chip__label">当前</span>
                        <span class="switch-chip__name">{{ currentSpaceName }}</span>
                    </div>
                    <span class="switch-route__arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-4-4 4 4-4 4" />
                        </svg>
                    </span>
                    <div class="switch-chip is-target">
                        <span class="switch-chip__dot is-to" />
                        <span class="switch-chip__label">目标</span>
                        <span class="switch-chip__name">{{ switchTargetName }}</span>
                    </div>
                </div>

                <div class="switch-impacts mt-4">
                    <div class="switch-impacts__title">切换后需注意</div>
                    <div v-for="(item, i) in TEAM_SPACE_SWITCH_IMPACTS" :key="i" class="switch-impacts__row">
                        <span class="switch-impacts__num">{{ i + 1 }}</span>
                        <span class="switch-impacts__text">{{ item }}</span>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <ElButton
                        class="!flex-1 !h-11 !rounded-xl !text-[15px]"
                        :disabled="switchLoading"
                        @click="switchVisible = false"
                        >取消</ElButton
                    >
                    <ElButton
                        type="primary"
                        class="!flex-1 !h-11 !rounded-xl !text-[15px] !font-semibold"
                        :loading="switchLoading"
                        @click="confirmSwitch">
                        确认切换
                    </ElButton>
                </div>
            </div>
        </ElDialog>
    </div>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { useAppStore } from "@/stores/app";
import DataPackage from "@/components/data-package/index.vue";
import { AppKeyEnum, LoginPopupTypeEnum } from "@/enums/appEnums";
import { getTeamInfo, getMyTeams, switchTeam, createTeam, joinTeam } from "@/api/team";
import { ElMessage } from "element-plus";
import feedback from "@/utils/feedback";
import { TEAM_SPACE_SWITCH_IMPACTS } from "@/utils/teamSwitchTip";
import { useUserLogin } from "../account/hooks/userLogin";
import FreeExperience from "./free-experience.vue";
import UserPanel from "./user-panel.vue";
import TokensPanel from "./tokens-panel.vue";
defineProps({
    isWechat: {
        type: Boolean,
        default: false,
    },
});

const route = useRoute();
const router = useRouter();

const userStore = useUserStore();

const { isLogin, toggleShowLogin } = toRefs(userStore);
const { changeLoginType } = useUserLogin();

const openLogin = () => {
    changeLoginType(LoginPopupTypeEnum.MOBILE_LOGIN);
    userStore.toggleShowLogin(true);
};
const openRegister = () => {
    changeLoginType(LoginPopupTypeEnum.REGISTER);
    userStore.toggleShowLogin(true);
};

interface Theme {
    shadow?: string;
    iconColor?: string;
    textColor?: string;
    hoverBgColor?: string;
    lineColor?: string;
}
const getTheme = computed<Theme>(() => {
    const { key, layout } = route.meta;
    if (layout == "wechat") {
        return {
            textColor: "#ffffff",
            shadow: "0 0 0 1px rgba(255,255,255,0.2)",
        };
    }
    switch (key) {
        case AppKeyEnum.DIGITAL_HUMAN:
        case AppKeyEnum.DRAWING:
        case AppKeyEnum.REDBOOK:
        case AppKeyEnum.SPH:
        case AppKeyEnum.MATRIX:
            return {
                shadow: "0 0 0 1px rgba(255,255,255,0.1)",
                iconColor: "rgba(255,255,255,0.8)",
                textColor: "rgba(255,255,255,0.8)",
                hoverBgColor: "rgba(255,255,255,0.1)",
                lineColor: "rgba(255,255,255,0.1)",
            };
        default:
            return {
                shadow: "0 0 0 1px rgba(0,0,0,0.05)",
                iconColor: "#000000",
                textColor: "#000000",
                hoverBgColor: "rgba(0,0,0,0.03)",
                lineColor: "#dcdfe6",
            };
    }
});

// 企业信息：有企业显示企业名(点进去管理)；没有则显示"企业管理"(点进去走开通流程)
const teamInfo = ref<any>(null);

/** 请求序号:解散/退出后作废进行中的旧响应,避免旧企业名盖回右上角 */
let teamInfoSeq = 0;
const loadTeamInfo = async () => {
    if (!isLogin.value) {
        teamInfo.value = null;
        return;
    }
    const seq = ++teamInfoSeq;
    try {
        const data = await getTeamInfo();
        if (seq !== teamInfoSeq) return; // 已被更新的刷新作废
        teamInfo.value = data;
    } catch (e) {
        if (seq !== teamInfoSeq) return;
        // 失败按无企业展示,清掉旧名称
        teamInfo.value = { in_team: 0 };
    }
};

onMounted(loadTeamInfo);
// 登录/退出时刷新
watch(isLogin, loadTeamInfo);
// 从企业控制台(/team)回来时刷新,拿到刚开通/改名的企业
watch(
    () => route.path,
    (_to, from) => {
        if (from === "/team") loadTeamInfo();
    },
);
// 在 /team 页加入/退出/解散/开通后不离开路由,靠 teamVersion 同步右上角
watch(
    () => userStore.teamVersion,
    () => {
        if (!isLogin.value) {
            teamInfo.value = null;
            myTeams.value = [];
            return;
        }
        // 控制台快照立刻生效;解散/退出时先清企业名与列表,再请求确认
        const hint = userStore.teamHeaderHint;
        if (hint) {
            teamInfoSeq += 1; // 作废进行中的旧 getTeamInfo
            if (Number(hint.in_team) === 1) {
                teamInfo.value = { in_team: 1, name: hint.name };
            } else {
                teamInfo.value = { in_team: 0 };
                myTeams.value = [];
            }
        }
        loadTeamInfo();
        loadMyTeams();
    },
);

// 我的企业列表(下拉)
const myTeams = ref<any[]>([]);
const hasOwnedTeam = computed(() => myTeams.value.some((t) => t.is_owner === 1));
// 当前是否在个人空间(没有任何企业处于 current)
const isPersonalCurrent = computed(() => !myTeams.value.some((t) => t.is_current === 1));
// 个人算力(后端 center 返回 personal_tokens;未部署时回退 tokens)
const personalTokens = computed(
    () => (userStore.userInfo as any)?.personal_tokens ?? (userStore.userInfo as any)?.tokens ?? 0,
);
const teamBtnLabel = computed(() => {
    if (!isLogin.value) return "企业管理";
    // 优先用 myTeams 的「当前」企业名:小程序切团队后点开下拉会先刷列表,
    // 避免右上角仍显示旧的 teamInfo 缓存名
    const current = myTeams.value.find((t) => Number(t.is_current) === 1);
    if (current?.name) return current.name;
    if (teamInfo.value?.in_team === 1 && teamInfo.value?.name) {
        return teamInfo.value.name;
    }
    return "企业管理";
});

const loadMyTeams = async () => {
    if (!isLogin.value) {
        myTeams.value = [];
        return;
    }
    try {
        myTeams.value = (await getMyTeams()) || [];
    } catch (e) {
        // 静默
    }
};
onMounted(loadMyTeams);
watch(isLogin, loadMyTeams);
const onTeamMenuOpen = (visible: boolean) => {
    if (!visible || !isLogin.value) return;
    // 打开下拉时同步刷新列表与右上角企业信息(覆盖小程序/其他端已切换的情况)
    loadMyTeams();
    loadTeamInfo();
};

// 页签重新可见时刷新空间状态(小程序切团队后回到 PC)
const onPageVisible = () => {
    if (document.visibilityState !== "visible" || !isLogin.value) return;
    loadMyTeams();
    loadTeamInfo();
};
onMounted(() => {
    document.addEventListener("visibilitychange", onPageVisible);
});
onUnmounted(() => {
    document.removeEventListener("visibilitychange", onPageVisible);
});

const switchVisible = ref(false);
const switchLoading = ref(false);
const switchTarget = ref<{ team_id: number; name: string } | null>(null);

const currentSpaceName = computed(() => {
    if (isPersonalCurrent.value) return "个人空间";
    const cur = myTeams.value.find((t) => t.is_current === 1);
    return String(cur?.name || teamInfo.value?.name || "当前空间");
});
const switchTargetName = computed(() => String(switchTarget.value?.name || ""));

const openSwitchConfirm = (teamId: number, name: string) => {
    switchTarget.value = { team_id: teamId, name };
    switchVisible.value = true;
};
const onSwitchClosed = () => {
    if (switchLoading.value) return;
    switchTarget.value = null;
};
const confirmSwitch = async () => {
    if (!switchTarget.value || switchLoading.value) return;
    const { team_id, name } = switchTarget.value;
    switchLoading.value = true;
    try {
        await switchTeam({ team_id });
        feedback.msgSuccess(team_id === 0 ? "已切换到个人空间" : `已切换到「${name}」`);
        location.reload();
    } catch (e: any) {
        switchLoading.value = false;
        feedback.msgError(typeof e === "string" ? e : e?.msg || "切换失败");
    }
};

const onTeamCommand = async (cmd: string) => {
    if (cmd === "create") {
        createName.value = "";
        createVisible.value = true;
        return;
    }
    if (cmd === "join") {
        joinCode.value = "";
        joinVisible.value = true;
        return;
    }
    if (cmd.startsWith("switch:")) {
        const teamId = Number(cmd.slice(7));
        if (teamId === 0) {
            if (isPersonalCurrent.value) return;
            openSwitchConfirm(0, "个人空间");
            return;
        }
        const target = myTeams.value.find((t) => t.team_id === teamId);
        if (!target) return;
        if (Number(target.expired) === 1) {
            feedback.msgWarning("该企业成员资格已过期，无法进入");
            return;
        }
        if (target.is_current === 1) return;
        openSwitchConfirm(teamId, String(target.name || "该企业"));
    }
};

/** 齿轮进入企业控制台：当前空间若成员已过期则拦截(防直进 /team) */
const openTeamConsole = () => {
    const current = myTeams.value.find((t) => Number(t.is_current) === 1);
    if (current && Number(current.expired) === 1) {
        feedback.msgWarning("该企业成员资格已过期，无法进入");
        return;
    }
    if (Number(teamInfo.value?.expired) === 1) {
        feedback.msgWarning("该企业成员资格已过期，无法进入");
        return;
    }
    router.push("/team");
};

/** 创建/加入后整页进入团队控制台(已在 /team 时 router.push 不会重挂载,需 reload) */
const enterTeamFresh = () => {
    if (route.path === "/team") {
        location.reload();
        return;
    }
    location.assign(router.resolve("/team").href);
};

// 创建企业弹窗
const createVisible = ref(false);
const createName = ref("");
const createLoading = ref(false);
const submitCreate = async () => {
    const name = createName.value.trim();
    if (!name) {
        feedback.msgWarning("请输入企业名称");
        return;
    }
    createLoading.value = true;
    try {
        await createTeam({ name });
        feedback.msgSuccess("创建成功");
        createVisible.value = false;
        createName.value = "";
        enterTeamFresh();
    } catch (e: any) {
        createLoading.value = false;
        feedback.msgError(typeof e === "string" ? e : e?.msg || "创建失败");
    }
};

// 加入团队弹窗
const joinVisible = ref(false);
const joinCode = ref("");
const joinLoading = ref(false);
const submitJoin = async () => {
    const code = joinCode.value.trim();
    if (code.length < 4) {
        feedback.msgWarning("请输入有效的邀请码");
        return;
    }
    joinLoading.value = true;
    try {
        await joinTeam({ code });
        feedback.msgSuccess("加入成功");
        joinVisible.value = false;
        joinCode.value = "";
        enterTeamFresh();
    } catch (e: any) {
        joinLoading.value = false;
        feedback.msgError(typeof e === "string" ? e : e?.msg || "加入失败");
    }
};

const appStore = useAppStore();

const openDataPackage = () => {
    appStore.openRecharge();
};
</script>

<style lang="scss">
.el-dropdown__popper:has(.team-dropdown) {
    border-radius: 18px !important;
    border: 1px solid rgba(15, 23, 42, 0.06) !important;
    box-shadow: 0 16px 48px -12px rgba(15, 23, 42, 0.22) !important;
    overflow: hidden;
}
.team-dropdown.el-dropdown-menu {
    padding: 8px !important;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: none;
}
.team-dropdown .el-dropdown-menu__item {
    border-radius: 12px;
    padding: 6px 10px;
    margin: 2px 0;
    line-height: 1.4;
    transition: background-color 0.18s ease, transform 0.18s ease;
}
.team-dropdown .el-dropdown-menu__item:not(.is-disabled):hover {
    background: rgba(0, 101, 251, 0.06);
    transform: translateX(2px);
}
.team-dropdown .el-dropdown-menu__item.is-disabled,
.team-dropdown .el-dropdown-menu__item.is-team-expired {
    cursor: not-allowed !important;
    opacity: 0.72;
    background: #f8fafc !important;
}
.team-dropdown .el-dropdown-menu__item.is-disabled:hover,
.team-dropdown .el-dropdown-menu__item.is-team-expired:hover {
    background: #f8fafc !important;
    transform: none;
}
.team-dropdown .el-dropdown-menu__item.is-divided {
    margin-top: 8px;
    padding-top: 10px;
    border-top: 1px solid rgba(15, 23, 42, 0.06);
}
.team-dropdown .el-dropdown-menu__item.is-divided::before {
    display: none;
}
/* 当前企业项:左侧高亮条 */
.team-dropdown .el-dropdown-menu__item.\!bg-primary\/5 {
    position: relative;
}
.team-dropdown .el-dropdown-menu__item.\!bg-primary\/5::after {
    content: "";
    position: absolute;
    left: 3px;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 18px;
    border-radius: 3px;
    background: var(--el-color-primary, #0065fb);
}

/* 加入团队弹窗 */
.el-dialog.team-join-dialog {
    border-radius: 20px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 24px 60px -12px rgba(15, 23, 42, 0.28);
}
.el-dialog.team-join-dialog .el-dialog__header {
    padding: 0;
    margin: 0;
}
.el-dialog.team-join-dialog .el-dialog__body {
    padding: 26px 24px 22px;
    position: relative;
}
.team-join-dialog .join-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 30px;
    height: 30px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    color: #94a3b8;
    transition: background-color 0.15s ease, color 0.15s ease;
    z-index: 1;
}
.team-join-dialog .join-close:hover {
    background: rgba(15, 23, 42, 0.06);
    color: #475569;
}
.team-join-dialog .join-input .el-input__wrapper {
    border-radius: 12px;
    padding: 6px 14px;
    box-shadow: 0 0 0 1px #e2e8f0 inset;
    transition: box-shadow 0.15s ease;
}
.team-join-dialog .join-input .el-input__wrapper:hover {
    box-shadow: 0 0 0 1px #cbd5e1 inset;
}
.team-join-dialog .join-input .el-input__wrapper.is-focus {
    box-shadow: 0 0 0 1.5px var(--el-color-primary, #0065fb) inset;
}

/* 切换空间：当前 → 目标 */
.team-join-dialog .switch-route {
    display: flex;
    align-items: stretch;
    gap: 10px;
}
.team-join-dialog .switch-chip {
    flex: 1;
    min-width: 0;
    border-radius: 14px;
    padding: 12px 12px 11px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
}
.team-join-dialog .switch-chip.is-target {
    background: rgba(0, 101, 251, 0.05);
    border-color: rgba(0, 101, 251, 0.16);
}
.team-join-dialog .switch-chip__dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 999px;
    margin-right: 6px;
    vertical-align: middle;
}
.team-join-dialog .switch-chip__dot.is-from {
    background: #94a3b8;
}
.team-join-dialog .switch-chip__dot.is-to {
    background: #0065fb;
}
.team-join-dialog .switch-chip__label {
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    vertical-align: middle;
}
.team-join-dialog .switch-chip__name {
    display: block;
    margin-top: 6px;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.35;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.team-join-dialog .switch-route__arrow {
    flex-shrink: 0;
    align-self: center;
    color: #94a3b8;
}
.team-join-dialog .switch-impacts {
    border-radius: 14px;
    padding: 12px 14px;
    background: #fffbeb;
    border: 1px solid #fde68a;
}
.team-join-dialog .switch-impacts__title {
    font-size: 12px;
    font-weight: 700;
    color: #b45309;
    margin-bottom: 8px;
}
.team-join-dialog .switch-impacts__row {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
.team-join-dialog .switch-impacts__row + .switch-impacts__row {
    margin-top: 8px;
}
.team-join-dialog .switch-impacts__num {
    flex-shrink: 0;
    width: 18px;
    height: 18px;
    margin-top: 1px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    font-size: 11px;
    font-weight: 700;
    color: #b45309;
    background: #fef3c7;
}
.team-join-dialog .switch-impacts__text {
    font-size: 13px;
    line-height: 1.45;
    color: #57534e;
}
</style>
