<template>
    <view class="min-h-screen bg-[#f5f8ff] flex flex-col">
        <view class="fixed top-0 left-0 w-full h-full bg-white z-10" v-if="loading">
            <view class="h-full flex flex-col items-center justify-center gap-[24rpx]">
                <u-loading mode="circle" size="60" color="#0065fb"></u-loading>
                <text class="text-[26rpx] text-[#8fa8c8]">海报生成中...</text>
            </view>
        </view>

        <view v-if="hasError" class="flex-1 flex flex-col items-center justify-center px-[80rpx]">
            <view
                class="w-[160rpx] h-[160rpx] rounded-full flex items-center justify-center mb-[40rpx]"
                style="background: linear-gradient(135deg, #eef4ff, #ddeaff)">
                <u-icon name="wifi-off" size="64" color="#90b0e8"></u-icon>
            </view>
            <view class="text-[32rpx] font-bold text-[#1a2b4a] mb-[16rpx]">二维码获取失败</view>
            <view class="text-[26rpx] text-[#8fa8c8] text-center leading-relaxed mb-[64rpx]">
                网络异常或服务繁忙，请稍后重试
            </view>
            <view
                class="w-full h-[96rpx] rounded-[100rpx] flex items-center justify-center gap-[12rpx]"
                style="background: linear-gradient(90deg, #0055d4, #4d9aff)"
                @click="handleRetry">
                <u-icon name="reload" size="32" color="#ffffff"></u-icon>
                <text class="text-[30rpx] font-bold text-white">重新加载</text>
            </view>
        </view>

        <canvas
            id="posterCanvas"
            canvas-id="posterCanvas"
            :style="{
                position: 'fixed',
                left: '1500rpx',
                top: '0',
                width: POSTER_W + 'px',
                height: POSTER_H + 'px',
            }" />

        <view v-if="!hasError && !loading" class="flex flex-col items-center p-[56rpx]">
            <view
                class="relative overflow-hidden rounded-[20rpx]"
                :style="{ width: `${posterDisplayW}rpx`, height: `${posterDisplayH}rpx` }">
                <image v-if="posterImage" :src="posterImage" class="w-full h-full" show-menu-by-longpress />
            </view>
            <text class="text-[24rpx] text-[#8fa8c8] mt-[24rpx]"> 图片发送给好友，长按识别即可注册 </text>
            <view class="flex gap-[24rpx] mt-[40rpx] w-full">
                <button
                    class="flex-1 flex items-center justify-center rounded-[100rpx] h-[100rpx] gap-[12rpx] border-none"
                    style="background: linear-gradient(90deg, #0055d4, #4d9aff)"
                    @click="savePoster">
                    <u-icon name="download" size="32" color="#ffffff"></u-icon>
                    <text class="text-white text-[30rpx] font-bold">下载保存</text>
                </button>
                <button
                    class="flex-1 flex items-center justify-center rounded-[100rpx] h-[100rpx] gap-[12rpx] bg-white border border-solid border-[#0065fb]/30"
                    @click="shareToWechat">
                    <u-icon name="weixin-fill" size="32" color="#0065fb"></u-icon>
                    <text class="text-[#0065fb] text-[30rpx] font-bold">发送给好友</text>
                </button>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import config from "@/config";
import { getAgentUserShareQrcode } from "@/api/user";
import { useUserStore } from "@/stores/user";

const instance = getCurrentInstance()!;
const userStore = useUserStore();
const { userInfo } = storeToRefs(userStore);

const CANVAS_ID = "posterCanvas";
const POSTER_W = 750;
const POSTER_H = 1150;
const posterDisplayW = 620;
const posterDisplayH = Math.round((POSTER_H / POSTER_W) * posterDisplayW);

const posterImage = ref("");
const shareQrcode = ref("");
const shareSn = ref("");
const loading = ref(true);
const hasError = ref(false);

let inited = false;
let ctx: any = null;

const posterBgUrl = config.baseUrl + "static/images/mp/agent_poster_bg.png";
const posterCardBgUrl = config.baseUrl + "static/images/mp/agent_poster_white_bg.png";

// ─── 图片下载 ─────────────────────────────────────────────────────────────
function loadImage(src: string): Promise<string> {
    return new Promise((resolve, reject) => {
        if (src.startsWith("http://") || src.startsWith("https://")) {
            uni.downloadFile({
                url: src,
                success: (res) => {
                    if (res.statusCode === 200) resolve(res.tempFilePath);
                    else reject(new Error(`downloadFile 状态异常: ${res.statusCode}`));
                },
                fail: (e) => reject(new Error(`downloadFile 失败: ${JSON.stringify(e)}`)),
            });
        } else {
            resolve(src);
        }
    });
}

// ─── 获取 ctx ─────────────────────────────────────────────────────────────
const getContext = (): Promise<any> => {
    if (ctx && inited) return Promise.resolve(ctx);
    return new Promise((resolve, reject) => {
        uni.createSelectorQuery()
            .in(instance)
            .select(`#${CANVAS_ID}`)
            .boundingClientRect()
            .exec((res: any) => {
                if (!res) return reject(new Error("canvas node 为空"));
                ctx = uni.createCanvasContext(CANVAS_ID, instance);
                inited = true;
                resolve(ctx);
            });
    });
};

// ─── 绘制圆形头像 ─────────────────────────────────────────────────────────
function drawCircleImage(src: string, cx: number, cy: number, r: number) {
    ctx.save();
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.clip();
    ctx.drawImage(src, cx - r, cy - r, r * 2, r * 2);
    ctx.restore();
}

// ─── 核心：绘制海报 ───────────────────────────────────────────────────────
const drawPoster = async () => {
    await getContext();

    const [bgImg, cardBgImg, avatarImg, qrcodeImg] = await Promise.all([
        loadImage(posterBgUrl),
        loadImage(posterCardBgUrl),
        loadImage(userInfo.value.avatar),
        loadImage(shareQrcode.value),
    ]);

    const W = POSTER_W;
    const H = POSTER_H;
    const cx = W / 2;

    // ── 1. 海报背景 ───────────────────────────────────────────────────────
    ctx.drawImage(bgImg, 0, 0, W, H);

    // ── 2. 白色卡片背景图 ─────────────────────────────────────────────────
    const avatarR = 90;
    const avatarBorder = 18;
    const avatarCY = 155;

    const cardX = 44;
    const cardY = avatarCY;
    const cardW = W - cardX * 2;
    const cardH = H - cardY - 110;

    ctx.drawImage(cardBgImg, cardX, cardY, cardW, cardH);

    // ── 3. 头像白边 + 头像 ────────────────────────────────────────────────
    ctx.save();
    ctx.beginPath();
    ctx.arc(cx, avatarCY, avatarR + avatarBorder, 0, Math.PI * 2);
    ctx.setFillStyle("#ffffff");
    ctx.fill();
    ctx.restore();
    drawCircleImage(avatarImg, cx, avatarCY, avatarR);

    // ── 4. 昵称 ───────────────────────────────────────────────────────────
    const nickFontSize = 48;
    const nickY = avatarCY + avatarR + avatarBorder + 36;
    ctx.setFontSize(nickFontSize);
    ctx.setFillStyle("#1a1a1a");
    ctx.setTextAlign("center");
    ctx.setTextBaseline("top");
    ctx.fillText(userInfo.value.nickname || "用户昵称", cx, nickY);

    // ── 5. 副标题 ─────────────────────────────────────────────────────────
    const subFontSize = 26;
    const subY = nickY + nickFontSize + 18;
    ctx.setFontSize(subFontSize);
    ctx.setFillStyle("#AAAAAA");
    ctx.fillText("邀你体验全新 AI 智能体", cx, subY);

    // ── 6. 二维码 ─────────────────────────────────────────────────────────
    const dashY = cardY + cardH * 0.42;
    const qrSize = 300;
    const qrY = dashY + 50;
    ctx.drawImage(qrcodeImg, cx - qrSize / 2, qrY, qrSize, qrSize);

    // ── 7. 邀请码 ─────────────────────────────────────────────────────────
    const snFontSize = 34;
    const snY = qrY + qrSize + 32;
    ctx.setFontSize(snFontSize);
    ctx.setFillStyle("#333333");
    ctx.fillText(shareSn.value || "", cx, snY);

    return new Promise<void>((resolve, reject) => {
        ctx.draw(false, () => {
            uni.canvasToTempFilePath(
                {
                    canvasId: CANVAS_ID,
                    x: 0,
                    y: 0,
                    width: POSTER_W,
                    height: POSTER_H,
                    destWidth: POSTER_W * 2,
                    destHeight: POSTER_H * 2,
                    fileType: "png",
                    quality: 1,
                    success: (fileRes: any) => {
                        posterImage.value = fileRes.tempFilePath;
                        loading.value = false;
                        resolve();
                    },
                    fail: (e: any) => reject(new Error("导出失败: " + JSON.stringify(e))),
                },
                instance
            );
        });
    });
};

const init = async () => {
    loading.value = true;
    hasError.value = false;
    posterImage.value = "";
    inited = false;
    ctx = null;

    try {
        const res = await getAgentUserShareQrcode({ path: "pages/index/index" });
        if (!res?.url) {
            hasError.value = true;
            loading.value = false;
            return;
        }
        shareQrcode.value = res.url;
        shareSn.value = res.sn;

        await nextTick();
        await drawPoster();
    } catch (e: any) {
        hasError.value = true;
        loading.value = false;
    }
};

const handleRetry = () => init();

const savePoster = () => {
    if (!posterImage.value) return;
    uni.showLoading({ title: "保存中", mask: true });
    uni.saveImageToPhotosAlbum({
        filePath: posterImage.value,
        success: () => {
            uni.hideLoading();
            uni.showToast({ title: "保存成功", icon: "success", duration: 3000 });
        },
        fail: (e: any) => {
            uni.hideLoading();
            uni.showToast({ title: e?.errMsg || "保存失败", icon: "error", duration: 3000 });
        },
    });
};

const shareToWechat = () => {
    if (!posterImage.value) return;
    const fileName = (posterImage.value.split("/").pop()?.split(".")[0] ?? "poster") + ".png";
    uni.shareFileMessage({
        filePath: posterImage.value,
        fileName,
        fail: (e: any) => {
            uni.showToast({ title: e?.errMsg || "分享失败", icon: "error", duration: 3000 });
        },
    });
};

onMounted(() => {
    init();
});
</script>
