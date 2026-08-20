import {
    createPersonAnalysis,
    editPerson,
    generatePersonAnalysisReport,
    getPersonClueWords,
    getPersonDetail,
    getPersonInteractionWords,
    getPersonTrackingWords,
} from "@/api/person";
import { TokensSceneEnum } from "@/enums/appEnums";
import { PersonTypeEnum } from "@/ai_modules/person/enums";
import { useUserStore } from "@/stores/user";
import { setFormData } from "@/utils/util";
import { computed, reactive, ref, shallowRef, toRefs } from "vue";
import {
    OPTIONS_CONFIG,
    PERSON_DETAIL_FORM_KEY,
    PERSON_TYPE_NAME_MAP,
    REPORT_MODEL_MAP,
    buildPersonPayload,
    buildReportContents,
    createDefaultBasicForm,
    createDefaultPersonFormData,
    createReportSnapshot,
    hasReportFieldsChanged,
    normalizeStringArray,
    syncBasicInfoToPayloadFields as syncBasicInfoToPersonFields,
    type PersonFormData,
} from "@/ai_modules/person/hooks/usePersonFormCore";

export const useEmployeeSetting = () => {
    const userStore = useUserStore();
    const { userTokens } = toRefs(userStore);

    const personId = ref("");
    const pageLoading = ref(false);
    const submitting = ref(false);
    const showRecorder = ref(false);
    const recorderRef = shallowRef<any>();
    const rechargePopupRef = shallowRef<any>();
    const originSnapshot = ref<Partial<PersonFormData> | null>(null);
    const basicForm = reactive(createDefaultBasicForm());
    const formData = reactive(createDefaultPersonFormData());

    const currentSubForm = computed<Record<string, any>>(() => formData as unknown as Record<string, any>);

    const getTokenScore = computed(() => {
        const tokenInfo = userStore.getTokenByScene(TokensSceneEnum.AI_PERSONA_REPORT);
        return Number(tokenInfo?.score || 0);
    });

    const getAnalysisTokenScore = computed(() => {
        const tokenInfo = userStore.getTokenByScene(TokensSceneEnum.AI_PERSONA_ANALYSIS);
        return Number(tokenInfo?.score || 0);
    });

    const footerButtonText = computed(() => "保存修改");

    const hasFormChanged = (): boolean => {
        return hasReportFieldsChanged(formData, originSnapshot.value);
    };

    const syncBasicInfoToPayloadFields = (): void => {
        syncBasicInfoToPersonFields(basicForm, formData);
    };

    const validateBaseInfo = (): boolean => {
        if (!(basicForm.industry_direction || []).length) {
            uni.showToast({ title: "请选择行业方向", icon: "none" });
            return false;
        }
        if (!basicForm.account_style) {
            uni.showToast({ title: "请选择账号风格", icon: "none" });
            return false;
        }
        if (!basicForm.action_goal) {
            uni.showToast({ title: "请选择行动目标", icon: "none" });
            return false;
        }
        return true;
    };

    const validateBusinessInfo = (): boolean => {
        syncBasicInfoToPayloadFields();
        const requiredFields: { key: keyof PersonFormData; label: string }[] = [
            { key: "main_business", label: "你在做什么 / 卖什么" },
            { key: "target_pain_points", label: "你想卖给谁 / 给谁看" },
            { key: "conversion_hook", label: "你比别人好在哪" },
        ];
        for (const field of requiredFields) {
            if (!String(formData[field.key] || "").trim()) {
                uni.showToast({ title: `请完善「${field.label}」`, icon: "none" });
                return false;
            }
        }
        return true;
    };

    const validate = (): boolean => {
        if (!validateBaseInfo()) return false;
        if (!validateBusinessInfo()) return false;
        return true;
    };

    const handleFooterClick = async (): Promise<void> => {
        await handleSubmit();
    };

    const handleAvatarUpdate = (imageUrl: string): void => {
        formData.avatar_url = imageUrl;
    };

    const openVoicePop = async (): Promise<void> => {
        await recorderRef.value?.authorize(recorderRef.value.proxy);
        showRecorder.value = true;
    };

    const parseAndMatchOptions = (value: string | undefined, availableOptions: readonly string[]): string[] => {
        if (!value) return [];
        const items = value
            .split(/[,，;；、\s]+/)
            .map((item) => item.trim())
            .filter((item) => item.length > 0);
        const matched: string[] = [];
        items.forEach((item) => {
            const exactMatch = availableOptions.find(
                (option) => option === item || option.includes(item) || item.includes(option),
            );
            if (exactMatch && !matched.includes(exactMatch)) matched.push(exactMatch);
        });
        return matched;
    };

    const recorderSuccess = async (res: any): Promise<void> => {
        showRecorder.value = false;
        const { message } = res;
        syncBasicInfoToPayloadFields();

        if (userTokens.value <= getAnalysisTokenScore.value) {
            rechargePopupRef.value?.open();
            return;
        }

        uni.showLoading({ title: "AI分析中...", mask: true });
        try {
            const analysisResult = await createPersonAnalysis({
                contents: message,
                model: formData.persona_type,
            });

            if (formData.persona_type === PersonTypeEnum.PERSONAL_IP) {
                const {
                    core_value,
                    highlight_story,
                    identity,
                    monetize_paths,
                    nickname,
                    personality_tags,
                    target_audience,
                } = analysisResult;
                if (nickname) formData.nickname = nickname;
                if (core_value) formData.main_business = core_value;
                if (highlight_story) formData.conversion_hook = highlight_story;
                if (target_audience) formData.target_pain_points = target_audience;
                if (identity) {
                    formData.identity = parseAndMatchOptions(identity, OPTIONS_CONFIG.identity);
                }
                if (personality_tags) {
                    formData.personality_tags = parseAndMatchOptions(personality_tags, OPTIONS_CONFIG.personality_tags);
                }
                if (monetize_paths) {
                    formData.monetize_paths = parseAndMatchOptions(monetize_paths, OPTIONS_CONFIG.monetize_paths);
                }
            } else if (formData.persona_type === PersonTypeEnum.BUSINESS_SERVICE) {
                const {
                    account_goal,
                    brand_name,
                    brand_tone,
                    industry_case,
                    main_product,
                    spokesperson,
                    target_customer,
                } = analysisResult;
                if (brand_name) formData.brand_name = brand_name;
                if (main_product) formData.main_business = main_product;
                if (industry_case) formData.conversion_hook = industry_case;
                if (target_customer) formData.target_pain_points = target_customer;
                if (account_goal) {
                    formData.account_goal = parseAndMatchOptions(account_goal, OPTIONS_CONFIG.account_goal);
                }
                if (spokesperson) {
                    formData.spokesperson = parseAndMatchOptions(spokesperson, OPTIONS_CONFIG.spokesperson);
                }
                if (brand_tone) {
                    formData.brand_tone = parseAndMatchOptions(brand_tone, OPTIONS_CONFIG.brand_tone);
                }
            } else if (formData.persona_type === PersonTypeEnum.LOCAL_BUSINESS) {
                const {
                    content_preference,
                    open_story,
                    signature_feature,
                    spokesperson,
                    store_atmosphere,
                    store_name,
                    target_customer,
                } = analysisResult;
                if (store_name) formData.store_name = store_name;
                if (signature_feature) formData.main_business = signature_feature;
                if (open_story) formData.conversion_hook = open_story;
                if (target_customer) formData.target_pain_points = target_customer;
                if (spokesperson) {
                    formData.store_spokesperson = parseAndMatchOptions(spokesperson, OPTIONS_CONFIG.store_spokesperson);
                }
                if (store_atmosphere) {
                    formData.store_atmosphere = parseAndMatchOptions(store_atmosphere, OPTIONS_CONFIG.store_atmosphere);
                }
                if (content_preference) {
                    formData.content_preference = parseAndMatchOptions(
                        content_preference,
                        OPTIONS_CONFIG.content_preference,
                    );
                }
            }

            uni.showToast({ title: "AI分析完成", icon: "none", duration: 3000 });
        } catch (error: any) {
            uni.showModal({
                title: "分析失败",
                content: error?.message || "语音分析失败，请检查网络后重试",
                showCancel: false,
                confirmText: "我知道了",
            });
        } finally {
            uni.hideLoading();
        }
    };

    const checkContentLength = (): Promise<boolean> => {
        const minTotalLength = 300;
        const totalLength = [
            formData.persona_name,
            formData.persona_desc,
            formData.content_focus,
            (basicForm.industry_direction || []).join(""),
            basicForm.content_direction,
            basicForm.target_audience,
            basicForm.account_style,
            basicForm.city_position,
            basicForm.action_goal,
            formData.main_business,
            formData.target_pain_points,
            formData.conversion_hook,
            formData.account_style,
        ].reduce((sum, value) => sum + String(value || "").trim().length, 0);

        if (totalLength >= minTotalLength) return Promise.resolve(true);
        return new Promise((resolve) => {
            uni.showModal({
                title: "内容过少",
                content: "当前内容过少，如果生成报告准确性将不确定，是否继续生成？",
                confirmText: "确定生成",
                cancelText: "继续修改",
                success: ({ confirm }) => resolve(confirm),
            });
        });
    };

    const confirmGenerateReportCost = (): Promise<boolean> => {
        return new Promise((resolve) => {
            uni.showModal({
                title: "重新生成报告",
                content: "人设信息已保存。检测到你改动了人设信息，现在需要重新生成报告、关键词等内容，并会额外产生算力消耗，是否同意继续？",
                confirmText: "同意生成",
                cancelText: "暂不生成",
                success: ({ confirm }) => resolve(confirm),
                fail: () => resolve(false),
            });
        });
    };

    const DETAIL_PAGE_ROUTE = "ai_modules/person/pages/detail/detail";

    // 保存成功后回到「人设管理」详情页。若本页正是从详情页跳转而来（栈中上一页即详情页），
    // 直接 navigateBack 让其 onShow 刷新即可；否则 redirectTo。
    // 避免用 redirectTo 在已有的详情页之上再叠一个相同的详情页，导致返回键看似「没有反应」。
    const goBackToDetail = (personaId: string): void => {
        const pages = getCurrentPages();
        const prevPage = pages[pages.length - 2] as any;
        const prevRoute = prevPage?.route || prevPage?.$page?.route || "";
        if (prevRoute.indexOf(DETAIL_PAGE_ROUTE) !== -1) {
            uni.navigateBack();
            return;
        }
        uni.redirectTo({
            url: `/${DETAIL_PAGE_ROUTE}?id=${personaId}&mode=edit&type=${formData.persona_type}`,
        });
    };

    const handleSubmit = async (): Promise<void> => {
        if (!validate()) return;

        const needGenerateReport = hasFormChanged();

        uni.showLoading({ title: "保存中...", mask: true });
        try {
            submitting.value = true;
            const payload = buildPersonPayload(basicForm, formData, {
                includeStoreAndShoppingFields: true,
            });
            const res = await editPerson({
                ...payload,
                id: personId.value,
                is_create_report: 0,
            });
            const personaId = res.persona_id || personId.value;
            uni.hideLoading();

            if (needGenerateReport) {
                const agreeGenerate = await confirmGenerateReportCost();
                if (!agreeGenerate) {
                    uni.showToast({ title: "配置已保存", icon: "none", duration: 1800 });
                    setTimeout(() => goBackToDetail(personaId), 900);
                    return;
                }

                const shouldContinue = await checkContentLength();
                if (!shouldContinue) return;

                if (userTokens.value <= getTokenScore.value) {
                    rechargePopupRef.value?.open();
                    return;
                }

                uni.showLoading({ title: "准备生成中...", mask: true });
                await editPerson({
                    ...payload,
                    id: personId.value,
                    is_create_report: 1,
                });
                uni.hideLoading();

                generatePersonAnalysisReport({
                    persona_id: personaId,
                    model: REPORT_MODEL_MAP[formData.persona_type],
                    contents: buildReportContents(formData),
                });
                getPersonClueWords({ id: personaId });
                getPersonInteractionWords({ id: personaId });
                getPersonTrackingWords({ id: personaId });
            }

            uni.showToast({ title: "配置已保存，AI 员工正在上岗", icon: "none", duration: 1800 });
            setTimeout(() => goBackToDetail(personaId), 900);
        } catch (e) {
            uni.showToast({ title: "保存失败，请重试", icon: "none" });
        } finally {
            uni.hideLoading();
            submitting.value = false;
        }
    };

    const init = async (): Promise<void> => {
        if (!personId.value) return;
        try {
            pageLoading.value = true;
            const data = await getPersonDetail({ id: personId.value });
            const personaType = data.persona_type as PersonTypeEnum;
            const formKey = PERSON_DETAIL_FORM_KEY[personaType];
            const subFormData = data[formKey] || {};
            const mergedData = { ...data, ...subFormData };
            setFormData(mergedData, formData);
            formData.identity = normalizeStringArray(subFormData.identity);
            if (formData.persona_type === PersonTypeEnum.LOCAL_BUSINESS) {
                formData.store_spokesperson = data.local?.spokesperson;
            }
            formData.global_option = subFormData.global_option ?? null;

            formData.main_business =
                data.main_business ||
                subFormData.core_value ||
                subFormData.main_product ||
                subFormData.signature_feature ||
                "";
            formData.target_pain_points =
                data.target_pain_points || subFormData.target_audience || subFormData.target_customer || "";
            formData.conversion_hook =
                data.conversion_hook ||
                subFormData.highlight_story ||
                subFormData.industry_case ||
                subFormData.open_story ||
                "";

            basicForm.ai_name = formData.persona_name;
            basicForm.intro = formData.persona_desc;
            basicForm.persona_type = formData.persona_type;
            // 行业方向回填：个人类型读 identity，企业/本地读 spokesperson（同槽位，均为一维数组）
            const identityRaw =
                formData.persona_type === PersonTypeEnum.PERSONAL_IP ? subFormData.identity : subFormData.spokesperson;
            const editIdentity = normalizeStringArray(identityRaw);
            const fallbackName = PERSON_TYPE_NAME_MAP[formData.persona_type] || "";
            basicForm.industry_direction = editIdentity.length ? editIdentity : fallbackName ? [fallbackName] : [];
            basicForm.content_direction =
                formData.content_focus ||
                formData.core_value ||
                formData.main_product ||
                formData.signature_feature ||
                "";
            basicForm.target_audience = formData.target_audience || formData.target_customer || "";
            // 账号风格 / 行动目标按类型读各自子字段，直接取自 subFormData，避免被 formData 的默认种子值污染
            const styleRaw =
                formData.persona_type === PersonTypeEnum.PERSONAL_IP
                    ? subFormData.personality_tags
                    : formData.persona_type === PersonTypeEnum.BUSINESS_SERVICE
                    ? subFormData.brand_tone
                    : subFormData.store_atmosphere;
            basicForm.account_style = normalizeStringArray(styleRaw)[0] || "";
            const goalRaw =
                formData.persona_type === PersonTypeEnum.PERSONAL_IP
                    ? subFormData.monetize_paths
                    : formData.persona_type === PersonTypeEnum.BUSINESS_SERVICE
                    ? subFormData.account_goal
                    : subFormData.content_preference;
            basicForm.action_goal = normalizeStringArray(goalRaw)[0] || "";

            // 购物车 / 商家定位：顶层字段回填（提交时经 includeStoreAndShoppingFields 透传）
            formData.is_shopping_cart = Number(data.is_shopping_cart) === 1 ? 1 : 0;
            formData.goods_name = data.goods_name || "";
            basicForm.auto_location = Number(data.is_store_position) === 1;
            basicForm.city_position = data.store_position || "";

            syncBasicInfoToPayloadFields();
            originSnapshot.value = createReportSnapshot(formData);
        } finally {
            pageLoading.value = false;
        }
    };

    const back = (): void => {
        uni.showModal({
            title: "提示",
            content: "退出后，您填写的信息将不会被保存",
            success: (res) => {
                if (res.confirm) uni.navigateBack();
            },
        });
    };

    const setPersonId = (id?: string): void => {
        personId.value = id ?? "";
    };

    return {
        back,
        basicForm,
        currentSubForm,
        footerButtonText,
        formData,
        getAnalysisTokenScore,
        handleAvatarUpdate,
        handleFooterClick,
        init,
        openVoicePop,
        pageLoading,
        personId,
        rechargePopupRef,
        recorderRef,
        recorderSuccess,
        setPersonId,
        showRecorder,
        submitting,
    };
};
