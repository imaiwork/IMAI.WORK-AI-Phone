import {
    meetingMinutesCreate,
    meetingMinutesLists,
    meetingMinutesRetry,
    meetingMinutesDelete,
} from "@/api/meeting_minutes";
import { TurnStatus } from "../enums";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";
import { usePaging } from "@/hooks/usePaging";
import { formatAudioTime } from "@/utils/util";

export default function useHandleApi(options?: any) {
    const { onSuccess } = options || {};

    const appStore = useAppStore();
    const userStore = useUserStore();
    const userTokens = computed(() => userStore.userInfo.tokens);
    const tokensValue = userStore.getTokenByScene(TokensSceneEnum.MEETING)?.score;

    const languageList = computed(() => {
        return (appStore.getMeetingConfig?.language || []).map((item: any) => ({
            text: item.name,
            value: item.code,
        }));
    });

    const targetLanguageList = computed(() => {
        return [
            { text: "不翻译", value: 0 },
            ...(appStore.getMeetingConfig?.translation || []).map((item: any) => ({
                text: item.name,
                value: item.code,
            })),
        ];
    });

    // 发言人
    const speakerOptions = [
        {
            value: 0,
            text: "不区分",
        },
        {
            value: 1,
            text: "单人",
        },
        {
            value: 2,
            text: "多人",
        },
    ];

    const { pager, getLists } = usePaging({
        fetchFun: meetingMinutesLists,
        params: {
            page_size: 10,
        },
    });

    const handleAgain = async (id: number) => {
        await uni.showModal({
            title: "确定要重新转写？",
            success: async (res) => {
                if (res.confirm) {
                    uni.showLoading({
                        title: "重试中",
                        mask: true,
                    });
                    try {
                        await meetingMinutesRetry({ id });
                        onSuccess?.("retry");
                        uni.hideLoading();
                        uni.showToast({
                            title: "重试成功",
                            icon: "none",
                            duration: 3000,
                        });
                    } catch (error: any) {
                        uni.hideLoading();
                        uni.showToast({
                            title: error || "重试失败",
                            icon: "none",
                            duration: 3000,
                        });
                    }
                }
            },
        });
    };
    const handleDelete = async (id: number) => {
        return new Promise((resolve, reject) => {
            uni.showModal({
                title: "确定删除此会议纪要吗？",
                success: async (res) => {
                    if (res.confirm) {
                        uni.showLoading({
                            title: "删除中",
                            mask: true,
                        });
                        try {
                            await meetingMinutesDelete({ id });
                            onSuccess?.("delete");
                            uni.hideLoading();
                            uni.showToast({
                                title: "删除成功",
                                icon: "none",
                                duration: 1000,
                            });
                            resolve(true);
                        } catch (error: any) {
                            uni.hideLoading();
                            uni.showToast({
                                title: error || "删除失败",
                                icon: "none",
                                duration: 1000,
                            });
                            reject(error);
                        }
                    }
                },
            });
        });
    };

    const handleItem = (row: any) => {
        const { status } = row;
        if (status == TurnStatus.SUCCESS) {
            uni.navigateTo({
                url: `/ai_modules/meeting_minutes/pages/detail/detail?id=${row.id}`,
            });
        } else if (status == TurnStatus.ERROR) {
            uni.$u.toast("文件转写失败，不可查看");
        } else if (status == TurnStatus.ING) {
            uni.$u.toast("文件转写中，不可查看");
        }
    };

    const formatName = (name: string) => {
        if (name.lastIndexOf(".") !== -1) {
            return name.slice(0, name.lastIndexOf("."));
        }
        return name;
    };

    const formatDate = (date: number) => {
        const time = new Date(date);
        return uni.$u.timeFormat(time, "mm/dd hh:MM");
    };

    const formatDuration = (duration: number) => {
        if (duration) {
            return formatAudioTime(duration / 1000);
        }
        return 0;
    };

    const getTags = computed(() => {
        return (item: any) => {
            const { response } = item || {};
            return response?.Result?.MeetingAssistance?.MeetingAssistance?.Keywords;
        };
    });

    const getResult = computed(() => {
        return (item: any) => {
            const { response } = item || {};
            return response?.Result?.Summarization?.Summarization?.ParagraphSummary;
        };
    });

    const getDuration = computed(() => {
        return (item: any) => {
            const { response } = item || {};
            const Duration = response?.Result?.Transcription?.Transcription?.AudioInfo?.Duration;
            return formatDuration(Duration);
        };
    });

    return {
        pager,
        userTokens,
        tokensValue,
        languageList,
        targetLanguageList,
        speakerOptions,
        getTags,
        getDuration,
        getResult,
        getLists,
        handleAgain,
        handleDelete,
        handleItem,
        formatDate,
        formatName,
        formatDuration,
    };
}
