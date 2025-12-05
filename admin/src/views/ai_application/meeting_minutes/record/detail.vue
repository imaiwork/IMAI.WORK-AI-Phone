<template>
	<div>
		<el-card class="!border-none" shadow="never">
			<el-page-header :content="detail.name" @back="$router.back()" />
		</el-card>
	</div>
	<div class="flex h-[80vh] mt-4 overflow-hidden">
		<div class="h-full flex flex-col flex-1 min-w-[568px] bg-[#f7f8fc]">
			<div class="text-lg font-bold mx-6 mt-6">语音转文字</div>
			<div class="grow min-h-0 flex flex-col relative">
				<div class="grow min-h-0 my-8 relative">
					<el-scrollbar
						v-if="getContentList.length > 0"
						ref="scrollbarContentRef"
						@scroll="handleContentScroll">
						<div class="px-6 pb-6">
							<div class="flex flex-col gap-y-6">
								<div
									ref="contentItemRef"
									v-for="(item, index) in getContentList">
									<div class="flex gap-4 items-center">
										<img
											class="w-6 h-6 rounded-full object-cover"
											:src="
												avatarList[item.SpeakerId - 1]
											" />
										<div
											class="flex gap-2 text-[#7F7F94] text-xs">
											<span
												>发言人{{
													item.SpeakerId
												}}</span
											>
											<span>{{
												getParagraphBeginTime(
													item.Words
												)
											}}</span>
										</div>
									</div>
									<div
										class="cursor-pointer py-4 px-6 bg-white rounded-lg mt-[10px] leading-[24px] text-[#585A73] hover:shadow-[0_0_0_2px_rgba(35,83,244,.4)]"
										:class="item.class"
										ref="paragraphRef"
										@click="
											handleParagraphClick(item.Words)
										">
										<span
											v-for="word in item.Words"
											:class="word.class"
											>{{ word.Text }}</span
										>
										<template v-if="detail.translation">
											<ElDivider
												class="!border-t-[#f0f0f0] !my-3" />
											<div
												class="break-all leading-[24px]">
												<span
													v-for="word in getCurrentTranslationContent(
														index
													).Sentences">
													<span>{{ word.Text }}</span>
												</span>
											</div>
										</template>
									</div>
								</div>
							</div>
						</div>
					</el-scrollbar>
					<div
						v-else
						class="flex flex-col justify-center items-center h-full">
						<el-empty />
					</div>
					<div
						class="absolute bottom-0 right-3 p-2 bg-[rgba(44,44,54,.8)] rounded-lg z-[888]"
						v-if="
							disabledContentScroll && currentParagraphIndex > -1
						"
						@click="positionContentScroll">
						<Icon
							name="local-icon-crosshair"
							:size="20"
							color="#ffffff"></Icon>
					</div>
				</div>
				<div class="h-[80px] flex-shrink-0">
					<AudioControl
						ref="audioControlRef"
						:music="detail.url"
						:content-list="getSections"
						@update-time="contentScroll"
						v-if="detail.id" />
				</div>
			</div>
		</div>
		<div class="flex-1 flex flex-col p-6 bg-white">
			<div>
				<ElTabs v-model="activeTypeTab">
					<ElTabPane label="导读" name="1"></ElTabPane>
					<ElTabPane label="脑图" name="2"></ElTabPane>
					<ElTabPane label="笔记" name="3"></ElTabPane>
				</ElTabs>
			</div>
			<Lead v-if="activeTypeTab === '1'" :detail="detail" />
			<MindMap v-if="activeTypeTab === '2'" :detail="detail" />
			<Note v-if="activeTypeTab === '3'" :content="detail.text" />
		</div>
	</div>
</template>

<script setup lang="ts">
import { getMeetingRecordDetail } from "@/api/ai_application/meeting_minutes";
import { useRoute } from "vue-router";
import { ElScrollbar } from "element-plus";
import { formatAudioTime } from "@/utils/util";
import Lead from "./components/lead.vue";
import MindMap from "./components/mind-map.vue";
import Note from "./components/note.vue";
import useAppStore from "@/stores/modules/app";
import AudioControl from "./components/audio-control.vue";

const route = useRoute();
const appStore = useAppStore();

const avatarList = computed(() => {
	return appStore.config.meeting_config.avatars;
});

const detail = ref<any>({});
const activeTypeTab = ref("1");

const contentItemRef = ref<HTMLElement[]>([]);
const paragraphRef = ref<HTMLElement[]>([]);

const currentParagraphIndex = ref(-1);
const disabledContentScroll = ref(false);
const isProgrammaticScroll = ref(false);
const scrollbarContentRef = ref<InstanceType<typeof ElScrollbar>>();
const audioControlRef = ref<InstanceType<typeof AudioControl>>();

const handleContentScroll = () => {
	if (isProgrammaticScroll.value) return;
	disabledContentScroll.value = true;
};

const contentScroll = (currentTime: number = 0) => {
	const contentList = getContentList.value;
	//@ts-ignore
	currentTime = parseInt(currentTime.toFixed(0));
	currentParagraphIndex.value = contentList.findIndex(
		(item: any) =>
			currentTime >= item.Words[0].Start &&
			currentTime <= item.Words[item.Words.length - 1].End
	);
	getContentList.value.forEach((item: any) => {
		item.class = "";
		item.Words.forEach((word: any) => {
			word.class = "";
		});
	});
	if (currentParagraphIndex.value !== -1) {
		const currentItem = getContentList.value[currentParagraphIndex.value];
		currentItem.class = "!bg-primary !text-[#ffffff99]";
		paragraphRef.value.forEach((dom, domIndex) => {
			const isCurrent = domIndex === currentParagraphIndex.value;
		});
		currentItem.Words.forEach((word: any, wordIndex: number) => {
			if (currentTime >= word.Start) {
				word.class = "!text-white";
			} else {
				word.class = "";
			}
		});
		const newParagraph = contentItemRef.value.filter(
			(item, index) => index <= currentParagraphIndex.value
		);
		const { scrollTop, clientHeight }: any =
			scrollbarContentRef.value?.wrapRef;
		const lastParagraph = newParagraph[newParagraph.length - 1];
		if (
			(lastParagraph.offsetTop + lastParagraph.clientHeight >
				scrollTop + clientHeight ||
				scrollTop > lastParagraph.offsetTop) &&
			!disabledContentScroll.value
		) {
			setScrollTop(lastParagraph.offsetTop);
		}
	}
};

const positionContentScroll = () => {
	setScrollTop(contentItemRef.value[currentParagraphIndex.value].offsetTop);
	setTimeout(() => {
		disabledContentScroll.value = false;
	}, 20);
};

const setScrollTop = (scrollTop: number) => {
	isProgrammaticScroll.value = true;
	scrollbarContentRef.value?.setScrollTop(scrollTop);
	setTimeout(() => {
		isProgrammaticScroll.value = false;
	}, 20);
};

// 获取段落开始时间
const getParagraphBeginTime = (words: any) => {
	if (words && words.length == 0) return "00:00";
	const beginTime = words[0].Start;
	return formatAudioTime(beginTime / 1000);
};

// 点击当前段落更新音频播放时间
const handleParagraphClick = (words: any) => {
	const beginTime = words[0].Start;
	audioControlRef.value?.seek(beginTime / 1000);
};

// 获取翻译的内容
const getCurrentTranslationContent = (index: number) => {
	const { response } = detail.value;
	const translationContent =
		response?.Result?.Translation?.Translation?.Paragraphs;
	if (translationContent && translationContent.length > 0) {
		return translationContent[index];
	}
	return [];
};

// 获取章节速览
const getSections = computed(() => {
	const { response } = detail.value;
	if (response) {
		return response.Result?.AutoChapters?.AutoChapters;
	}
});

const getContentList = computed(() => {
	const { response } = detail.value;
	const contentList =
		response?.Result?.Transcription?.Transcription?.Paragraphs;
	if (contentList && contentList.length > 0) {
		return contentList;
	}
	return [];
});

const getDetail = async () => {
	const result = await getMeetingRecordDetail({ id: route.query.id });
	detail.value = result;
};

onMounted(() => {
	getDetail();
});
</script>

<style scoped></style>
