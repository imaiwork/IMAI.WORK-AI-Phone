import feedback from "@/utils/feedback";
import {
    getTeamTenant,
    setTeamTenant,
    getMnpVersion,
    uploadMnp,
    getAttributedUsers,
    setSiteUserTokens,
    removeTeamMember,
    upgradeOem,
    generateTeamCard,
    getTeamCardLists,
    getTeamCardMemberLevels,
    transferTeamCard,
    deleteTeamCard,
    getMemberOptions,
} from "@/api/team";
import { usePaging } from "@/composables/usePaging";
import { useGlobalSpin } from "@/composables/useSpinLoading";
import { useUserStore } from "@/stores/user";
import { getApiPrefix, getApiUrl } from "@/utils/env";
import { errText } from "./helpers";

interface UseTeamBrandOptions {
    refresh: () => Promise<void>;
}

/** 品牌管理:品牌/小程序配置 + 卡密 + 站点用户 + OEM 升级 */
export function useTeamBrand({ refresh }: UseTeamBrandOptions) {
    const userStore = useUserStore();

    // 二级页签:site | mnp | card | users
    const brandTab = ref("site");
    // 代码包是否已上传(以服务端磁盘为准，刷新可回显)
    const mnpUploaded = ref(false);

    // ---------- 品牌 / 小程序 ----------
    const tenant = reactive<any>({
        domain: "",
        brand: { name: "", web_logo: "", pc_logo: "", admin_qr: "", icp_number: "", company_name: "" },
        mnp: {
            app_id: "",
            app_secret: "",
            original_id: "",
            name: "",
            qr_code: "",
            has_app_secret: false,
            has_private_key: false,
            private_key: "",
            app_version: "",
            audit: 0,
        },
        // 微信公众平台需配置的站点域名 + 主站域名 + OSS（只读展示）
        mnp_domains: {
            request_domain: "",
            socket_domain: "",
            upload_file_domain: "",
            download_file_domain: "",
            udp_domain: "",
            business_domain: "",
            site: {
                host: "",
                request_domain: "",
                socket_domain: "",
                upload_file_domain: "",
                download_file_domain: "",
                udp_domain: "",
                business_domain: "",
            },
            main: {
                host: "",
                request_domain: "",
                socket_domain: "",
                upload_file_domain: "",
                download_file_domain: "",
                udp_domain: "",
                business_domain: "",
            },
            oss_domain: "",
            oss_engine: "",
            oss_engine_name: "",
        },
    });

    const loadTenant = async () => {
        try {
            const res: any = await getTeamTenant();
            if (!res) return;
            tenant.domain = res.domain || "";
            tenant.brand = {
                name: "",
                web_logo: "",
                pc_logo: "",
                admin_qr: "",
                icp_number: "",
                company_name: "",
                ...(res.brand || {}),
            };
            const mnp = res.mnp || {};
            tenant.mnp = {
                app_id: "",
                original_id: "",
                name: "",
                qr_code: "",
                app_version: "",
                audit: 0,
                ...mnp,
                // 接口脱敏：只回 has_* 标志(0/1)，规范化为布尔，便于表单回显「已配置」
                has_app_secret: !!Number(mnp.has_app_secret),
                has_private_key: !!Number(mnp.has_private_key),
                has_mnp_code: !!Number(mnp.has_mnp_code),
                // 明文不回传，本地输入框保持空，靠 has_* + 文案提示
                app_secret: "",
                private_key: "",
            };
            const emptyHostDomains = {
                host: "",
                request_domain: "",
                socket_domain: "",
                upload_file_domain: "",
                download_file_domain: "",
                udp_domain: "",
                business_domain: "",
            };
            const rawDomains = res.mnp_domains || {};
            tenant.mnp_domains = {
                request_domain: "",
                socket_domain: "",
                upload_file_domain: "",
                download_file_domain: "",
                udp_domain: "",
                business_domain: "",
                oss_domain: "",
                oss_engine: "",
                oss_engine_name: "",
                ...rawDomains,
                site: { ...emptyHostDomains, ...(rawDomains.site || {}) },
                main: { ...emptyHostDomains, ...(rawDomains.main || {}) },
            };
            // 服务端已落盘的代码包，刷新后回显为已上传
            if (tenant.mnp.has_mnp_code) mnpUploaded.value = true;
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };

    const onBrandIcon = (res: any) => (tenant.brand.web_logo = res?.data?.uri || "");
    const onBrandLogo = (res: any) => (tenant.brand.pc_logo = res?.data?.uri || "");
    const onBrandQr = (res: any) => (tenant.brand.admin_qr = res?.data?.uri || "");
    const onMnpQrCode = (res: any) => (tenant.mnp.qr_code = res?.data?.uri || "");

    const onSaveBrand = async () => {
        const domain = String(tenant.domain || "").trim();
        const name = String(tenant.brand?.name || "").trim();
        if (!domain) return feedback.msgWarning("请填写站点域名");
        if (!name) return feedback.msgWarning("请填写站点标题");
        if (!tenant.brand?.web_logo) return feedback.msgWarning("请上传站点 ICON");
        if (!tenant.brand?.pc_logo) return feedback.msgWarning("请上传站点 LOGO");
        try {
            await setTeamTenant({
                domain,
                brand: {
                    name,
                    web_logo: tenant.brand.web_logo,
                    pc_logo: tenant.brand.pc_logo,
                    admin_qr: tenant.brand.admin_qr,
                    icp_number: String(tenant.brand.icp_number || "").trim(),
                    company_name: String(tenant.brand.company_name || "").trim(),
                },
            });
            feedback.msgSuccess("保存成功");
            await loadTenant();
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };

    const onSaveMnp = async () => {
        const name = String(tenant.mnp?.name || "").trim();
        const originalId = String(tenant.mnp?.original_id || "").trim();
        const appId = String(tenant.mnp?.app_id || "").trim();
        const appSecret = String(tenant.mnp?.app_secret || "").trim();
        const privateKey = String(tenant.mnp?.private_key || "").trim();
        if (!name) return feedback.msgWarning("请填写小程序名称");
        if (!appId) return feedback.msgWarning("请填写 AppID");
        if (!appSecret && !tenant.mnp?.has_app_secret) return feedback.msgWarning("请填写 AppSecret");
        if (!privateKey && !tenant.mnp?.has_private_key) return feedback.msgWarning("请填写代码上传私钥");
        try {
            const mnp: any = {
                app_id: appId,
                app_secret: appSecret,
                original_id: originalId,
                name,
                qr_code: tenant.mnp.qr_code,
                audit: tenant.mnp.audit ? 1 : 0,
            };
            if (privateKey) mnp.private_key = privateKey;
            await setTeamTenant({ mnp });
            feedback.msgSuccess("保存成功");
            await loadTenant();
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };

    // ---------- 上传小程序 ----------
    const mnpUploadAction = computed(() => `${getApiUrl()}${getApiPrefix()}/team/uploadMnpCode`);
    const mnpForm = reactive({ upload_version: "", upload_desc: "" });

    const onMnpCodeUploaded = (res: any) => {
        mnpUploaded.value = true;
        feedback.msgSuccess(res?.msg || "代码包上传成功");
    };
    const loadMnpVersion = async () => {
        try {
            const res: any = await getMnpVersion();
            if (res?.version && !mnpForm.upload_version) mnpForm.upload_version = res.version;
            if (Number(res?.has_mnp_code)) mnpUploaded.value = true;
        } catch (e) {
            // 忽略:版本号仅用于预填
        }
    };
    const mnpSubmitting = ref(false);
    const { show: showSpin, hide: hideSpin } = useGlobalSpin();
    const onSubmitMnp = async () => {
        if (mnpSubmitting.value) return;
        if (!mnpUploaded.value) return feedback.msgWarning("请先上传小程序代码包");
        if (!mnpForm.upload_version.trim()) return feedback.msgWarning("请输入版本号");
        mnpSubmitting.value = true;
        showSpin({ text: "正在提交到微信，请稍候..." });
        try {
            const res: any = await uploadMnp({
                upload_version: mnpForm.upload_version.trim(),
                upload_desc: mnpForm.upload_desc.trim(),
            });
            feedback.msgSuccess(res?.msg || "提交成功");
        } catch (e) {
            feedback.msgError(errText(e));
        } finally {
            hideSpin();
            mnpSubmitting.value = false;
        }
    };

    // ---------- 卡密(算力卡 type=5 / 会员兑换码 type=6) ----------
    const showGenerateCard = ref(false);
    const generatingCard = ref(false);
    const memberLevels = ref<{ id: number; level_name: string }[]>([]);
    const memberLevelsLoading = ref(false);
    const cardForm = reactive({
        type: 5,
        tokens: 100,
        member_level_id: "" as number | "",
        member_days: 30,
        count: 1,
        rule_type: 1,
        validRange: null as any,
        remark: "",
    });
    const cardTotalCost = computed(() => {
        if (Number(cardForm.type) !== 5) return 0;
        const t = Number(cardForm.tokens) || 0;
        const c = Number(cardForm.count) || 0;
        return Math.round(t * c * 100) / 100;
    });
    const {
        pager: cardPager,
        getLists: getCardLists,
        resetPage: resetCard,
    } = usePaging({ fetchFun: getTeamCardLists });

    const loadMemberLevels = async () => {
        memberLevelsLoading.value = true;
        try {
            const res: any = await getTeamCardMemberLevels();
            memberLevels.value = Array.isArray(res) ? res : Array.isArray(res?.lists) ? res.lists : [];
        } catch (e) {
            memberLevels.value = [];
            feedback.msgError(errText(e));
        } finally {
            memberLevelsLoading.value = false;
        }
    };

    watch(showGenerateCard, (v) => {
        if (v) loadMemberLevels();
    });

    const onGenerateCard = async () => {
        const type = Number(cardForm.type) === 6 ? 6 : 5;
        const count = Number(cardForm.count);
        if (!Number.isInteger(count) || count < 1) return feedback.msgWarning("卡密数量须为正整数");

        const [vs, ve] = Array.isArray(cardForm.validRange) ? cardForm.validRange : [null, null];
        if (!vs || !ve) return feedback.msgWarning("请选择生效时间");
        const validStart = Math.floor(new Date(vs).getTime() / 1000);
        const validEnd = Math.floor(new Date(ve).getTime() / 1000);
        if (!validStart || !validEnd || validEnd <= validStart) {
            return feedback.msgWarning("结束时间需晚于开始时间");
        }

        const payload: Record<string, any> = {
            type,
            count,
            rule_type: cardForm.rule_type,
            valid_start_time: validStart,
            valid_end_time: validEnd,
            remark: cardForm.remark.trim(),
        };

        if (type === 5) {
            const tokens = Number(cardForm.tokens);
            if (!Number.isFinite(tokens) || tokens <= 0) return feedback.msgWarning("请输入每张算力");
            if (!/^\d+(\.\d{1,2})?$/.test(String(cardForm.tokens))) {
                return feedback.msgWarning("每张算力最多保留两位小数");
            }
            payload.tokens = Math.round(tokens * 100) / 100;
        } else {
            const levelId = Number(cardForm.member_level_id) || 0;
            const days = Number(cardForm.member_days);
            if (!levelId) return feedback.msgWarning("请选择会员等级");
            if (!Number.isInteger(days) || days < 1) return feedback.msgWarning("请输入有效会员天数");
            payload.member_level_id = levelId;
            payload.member_days = days;
        }

        try {
            const confirmMsg =
                type === 5
                    ? `本次生成 ${count} 张算力卡（每张 ${payload.tokens} 算力），将从你的剩余算力中扣除 ${cardTotalCost.value} 算力。确定继续吗？`
                    : `本次生成 ${count} 张会员兑换码（${payload.member_days} 天），确定继续吗？`;
            await feedback.confirm(confirmMsg, "生成卡密", {
                type: "warning",
                confirmButtonText: "确定生成",
                cancelButtonText: "取消",
            });
            generatingCard.value = true;
            await generateTeamCard(payload);
            feedback.msgSuccess("生成成功");
            showGenerateCard.value = false;
            await Promise.all([resetCard(), refresh()]);
        } catch (e) {
            if (e !== "cancel") feedback.msgError(errText(e));
        } finally {
            generatingCard.value = false;
        }
    };

    const showTransferCard = ref(false);
    const transferringCard = ref(false);
    const transferCardRow = ref<any>(null);
    const transferToUserId = ref<number | "">("");
    const transferMembers = ref<any[]>([]);

    const openTransferCard = async (row: any) => {
        if (Number(row?.status) === 1 || Number(row?.remaining_uses) <= 0) {
            return feedback.msgWarning("仅未使用的卡密可转移");
        }
        transferCardRow.value = row;
        transferToUserId.value = "";
        transferMembers.value = [];
        showTransferCard.value = true;
        try {
            const rows: any = await getMemberOptions();
            // 接口 data 为数组；兼容偶发包一层 lists/data
            transferMembers.value = Array.isArray(rows)
                ? rows
                : Array.isArray(rows?.lists)
                ? rows.lists
                : Array.isArray(rows?.data)
                ? rows.data
                : [];
        } catch (e) {
            transferMembers.value = [];
            feedback.msgError(errText(e));
        }
    };

    const onTransferCard = async () => {
        const cardId = Number(transferCardRow.value?.id) || 0;
        const toUserId = Number(transferToUserId.value) || 0;
        if (!cardId) return;
        if (!toUserId) return feedback.msgWarning("请选择接收成员");
        try {
            transferringCard.value = true;
            await transferTeamCard({ id: cardId, to_user_id: toUserId });
            feedback.msgSuccess("转移成功");
            showTransferCard.value = false;
            getCardLists();
        } catch (e) {
            feedback.msgError(errText(e));
        } finally {
            transferringCard.value = false;
        }
    };

    const onDeleteCard = async (row: any) => {
        const isMember = Number(row?.type) === 6;
        try {
            useNuxtApp().$confirm({
                title: isMember
                    ? "确定删除该会员兑换码？删除后不可恢复。"
                    : "确定删除该卡密？未使用的剩余次数将按面值退回你的算力。",
                message: "删除卡密",
                confirmButtonText: isMember ? "删除" : "删除并退回",
                cancelButtonText: "取消",
                onConfirm: async () => {
                    await deleteTeamCard({ id: row.id });
                    feedback.msgSuccess(isMember ? "已删除" : "已删除，算力已退回");
                    getCardLists();
                    await refresh();
                },
            });
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };

    // ---------- 站点归属用户 ----------
    const siteUsers = ref<any[]>([]);
    const loadSiteUsers = async () => {
        try {
            siteUsers.value = (await getAttributedUsers()) || [];
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };
    watch(brandTab, (t) => {
        if (t === "users") loadSiteUsers();
    });

    const onAdjustSiteUser = async (row: any) => {
        try {
            const { value } = await feedback.prompt(
                `设置「${row.nickname}」的算力（当前 ${row.tokens}）。调高从你的算力扣除，调低退回你。`,
                "调整算力",
                {
                    inputValue: String(row.tokens ?? 0),
                    inputPattern: /^\d+(\.\d{1,2})?$/,
                    inputErrorMessage: "请输入正确的数量",
                    confirmButtonText: "保存",
                    cancelButtonText: "取消",
                },
            );
            await setSiteUserTokens({ user_id: row.id, tokens: Number(value) });
            feedback.msgSuccess("已调整");
            await Promise.all([loadSiteUsers(), refresh()]);
        } catch (e) {
            if (e !== "cancel") feedback.msgError(errText(e));
        }
    };

    const onRemoveSiteUser = async (row: any) => {
        try {
            await feedback.confirm(
                `确定将「${row.nickname}」移出你的站点吗？解除归属后该用户回到普通用户，其算力保留。`,
                "移除用户",
                { type: "warning", confirmButtonText: "移除", cancelButtonText: "取消" },
            );
            await removeTeamMember({ user_id: row.id });
            feedback.msgSuccess("已移除");
            await loadSiteUsers();
        } catch (e) {
            if (e !== "cancel") feedback.msgError(errText(e));
        }
    };

    // ---------- OEM 升级 ----------
    const showUpgrade = ref(false);
    const upgrading = ref(false);
    const upgradeForm = reactive({ mobile: "", code: "" });
    const smsCountdown = ref(0);
    watch(showUpgrade, (v) => {
        if (v && !upgradeForm.mobile) {
            upgradeForm.mobile = (userStore as any).userInfo?.mobile || "";
        }
    });
    const onSendSms = async () => {
        const mobile = String(upgradeForm.mobile || "").trim();
        if (!mobile) return feedback.msgWarning("请输入手机号");
        if (!/^1\d{10}$/.test(mobile)) return feedback.msgWarning("手机号格式不正确");
        const bound = String((userStore as any).userInfo?.mobile || "").trim();
        if (bound && mobile !== bound) {
            return feedback.msgWarning("请使用账号绑定的手机号获取验证码");
        }
        try {
            const { smsSend } = await import("@/api/app");
            await smsSend({ mobile, scene: "YZMDL" });
            feedback.msgSuccess("验证码已发送");
            smsCountdown.value = 60;
            const timer = setInterval(() => {
                smsCountdown.value--;
                if (smsCountdown.value <= 0) clearInterval(timer);
            }, 1000);
        } catch (e) {
            feedback.msgError(errText(e));
        }
    };
    const onUpgradeOem = async () => {
        const mobile = String(upgradeForm.mobile || "").trim();
        const code = String(upgradeForm.code || "").trim();
        if (!mobile) return feedback.msgWarning("请输入手机号");
        if (!/^1\d{10}$/.test(mobile)) return feedback.msgWarning("手机号格式不正确");
        if (!code) return feedback.msgWarning("请输入验证码");
        if (upgrading.value) return;
        upgrading.value = true;
        try {
            await upgradeOem({ mobile, code });
            feedback.msgSuccess("预缴费成功，等待站长审核");
            showUpgrade.value = false;
            upgradeForm.code = "";
            await refresh();
        } catch (e) {
            feedback.msgError(errText(e));
        } finally {
            upgrading.value = false;
        }
    };

    return {
        brandTab,
        tenant,
        loadTenant,
        onBrandIcon,
        onBrandLogo,
        onBrandQr,
        onMnpQrCode,
        onSaveBrand,
        onSaveMnp,
        mnpUploadAction,
        mnpUploaded,
        mnpForm,
        onMnpCodeUploaded,
        loadMnpVersion,
        mnpSubmitting,
        onSubmitMnp,
        showGenerateCard,
        generatingCard,
        cardForm,
        cardTotalCost,
        memberLevels,
        memberLevelsLoading,
        cardPager,
        getCardLists,
        resetCard,
        onGenerateCard,
        showTransferCard,
        transferringCard,
        transferCardRow,
        transferToUserId,
        transferMembers,
        openTransferCard,
        onTransferCard,
        onDeleteCard,
        siteUsers,
        loadSiteUsers,
        onAdjustSiteUser,
        onRemoveSiteUser,
        showUpgrade,
        upgrading,
        upgradeForm,
        smsCountdown,
        onSendSms,
        onUpgradeOem,
    };
}
