import {
    meetingMinutesCreate,
    meetingMinutesLists,
    meetingMinutesRetry,
    meetingMinutesDelete,
} from "@/api/meeting_minutes";
import { TurnStatus } from "../_enums";
import { useUserStore } from "@/stores/user";
import { TokensSceneEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";

export default function useHandleApi(options?: any) {
    const nuxtApp = useNuxtApp();

    const { onSuccess } = options || {};

    const router = useRouter();
    const appStore = useAppStore();
    const userStore = useUserStore();
    const userTokens = computed(() => userStore.userInfo.tokens);
    const tokensValue = computed(() => userStore.getTokenByScene(TokensSceneEnum.MEETING) || {});

    const languageList = computed(() => {
        return appStore.getMeetingConfig.language;
    });

    const targetLanguageList = computed(() => {
        return [{ name: "不翻译", code: 0 }, ...appStore.getMeetingConfig.translation];
    });

    // 发言人
    const speakerOptions = [
        {
            value: 0,
            label: "不区分",
        },
        {
            value: 1,
            label: "单人",
        },
        {
            value: 2,
            label: "多人",
        },
    ];

    const { pager, getLists } = usePaging({
        fetchFun: meetingMinutesLists,
        params: {
            page_size: 10,
        },
    });

    const handleAgain = async (id: number) => {
        nuxtApp.$confirm({
            message: "确定要重新转写？",
            onConfirm: async () => {
                try {
                    await meetingMinutesRetry({ id });
                    feedback.msgSuccess("重试成功");
                    onSuccess?.("retry");
                } catch (error) {
                    feedback.msgError(error || "重试失败");
                }
            },
        });
    };
    const handleDelete = async (id: number) => {
        nuxtApp.$confirm({
            message: "确定删除此会议纪要吗？",
            onConfirm: async () => {
                try {
                    await meetingMinutesDelete({ id });
                    feedback.msgSuccess("删除成功");
                    onSuccess?.("delete");
                } catch (error) {
                    feedback.msgError(error || "删除失败");
                }
            },
        });
    };

    const handleItem = (row: any) => {
        const { status } = row;
        if (status == TurnStatus.SUCCESS) {
            router.push({
                path: "/app/meeting_minutes/detail",
                query: {
                    id: row.id,
                },
            });
        } else if (status == TurnStatus.ERROR) {
            feedback.msgWarning("文件转写失败，不可查看");
        } else if (status == TurnStatus.ING) {
            feedback.msgWarning("文件转写中，不可查看");
        }
    };

    interface TrainResult {
        type: string;
        fileName: string;
        content: string;
    }

    const handleTrain = async (data: any, cb: (result: TrainResult) => void) => {
        const { Result } = data.response || {};
        const Paragraphs_ZH = Result?.Transcription?.Transcription?.Paragraphs || [];
        const Paragraphs_EN = Result?.Translation?.Translation?.Paragraphs || [];
        const AutoChapters = Result?.AutoChapters?.AutoChapters || [];
        const Actions = Result?.MeetingAssistance?.MeetingAssistance.Actions || [];
        const QuestionsAnsweringSummary = Result?.Summarization?.Summarization.QuestionsAnsweringSummary || [];
        if (
            (Paragraphs_ZH && Paragraphs_ZH.length) ||
            (Paragraphs_EN && Paragraphs_EN.length) ||
            (AutoChapters && AutoChapters.length) ||
            (Actions && Actions.length) ||
            (QuestionsAnsweringSummary && QuestionsAnsweringSummary.length)
        ) {
            let content = "";

            Paragraphs_ZH.forEach((item) => {
                content += item.Words.reduce((acc, cur) => {
                    return acc + cur.Text;
                }, "");
            });

            content += "\n\n";

            Paragraphs_EN.forEach((item) => {
                content += item.Sentences.reduce((acc, cur) => {
                    return acc + cur.Text;
                }, "");
            });

            content += "\n\n";

            AutoChapters.forEach((item) => {
                content += item.Headline + "\n" + item.Summary;
            });

            content += "\n\n";

            Actions.forEach((item) => {
                content += item.Text;
            });

            content += "\n\n";

            QuestionsAnsweringSummary.forEach((item) => {
                content += item.Question + "\n" + item.Answer;
            });

            cb({
                type: "txt",
                fileName: data.name,
                content,
            });
        } else {
            feedback.notify("暂无内容");
        }
    };

    const formatName = (name: string) => {
        if (name.lastIndexOf(".") !== -1) {
            return name.slice(0, name.lastIndexOf("."));
        }
        return name;
    };

    const getDuration = (duration: number) => {
        if (duration) {
            return formatAudioTime(duration / 1000);
        }
        return 0;
    };

    return {
        pager,
        userTokens,
        tokensValue,
        languageList,
        targetLanguageList,
        speakerOptions,
        getLists,
        handleAgain,
        handleDelete,
        handleTrain,
        handleItem,
        formatName,
        getDuration,
    };
}
