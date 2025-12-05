import { getCurrentInstance, onBeforeUnmount, ref, shallowRef } from "vue";
//#ifdef H5
import Recorder from "recorder-core/recorder.mp3.min";
// #endif
import Mp3 from "js-mp3";
//@ts-ignore
import genFFT from "@/lib/fft.js";

interface H5RecordOptions {
    type: "mp3" | "wav";
    bitRate: number;
    sampleRate: number;
    duration: number;
}

type Options = Partial<H5RecordOptions & UniApp.RecorderManagerStartOptions>;
interface RecorderResult {
    tempFilePath: string;
    duration: number;
}

interface DataResult {
    pcmData: Int16Array;
    powerLevel: number;
    sampleRate: number;
}
interface callbacks {
    onstart?(): void;
    onstop?(result: RecorderResult): void;
    ondata?(result: DataResult): void;
    onerror?(error: any): void;
}

export const useRecorder = (callbacks?: callbacks, options?: Options) => {
    options = options || {
        type: "mp3",
        sampleRate: 16000,
        bitRate: 32,
        duration: 5 * 60 * 1000,
        numberOfChannels: 1, //录音通道数
        encodeBitRate: 64000,
        format: "mp3", //音频格式，有效值 aac/mp3 等
        frameSize: 1200, //指定帧大小，单位 KB
    };
    const isRecording = ref(false);
    const mediaRecorder = shallowRef();
    // const isAuth = ref(false)
    const createMediaRecorder = () => {
        //#ifdef H5
        mediaRecorder.value = Recorder({
            ...options,
            async onProcess(pcmdata: Int16Array[], powerLevel: number, duration: number, sampleRate: number) {
                callbacks?.ondata?.({
                    pcmData: pcmdata[pcmdata.length - 1],
                    powerLevel,
                    sampleRate,
                });
            },
        });
        //#endif
        //#ifndef H5
        mediaRecorder.value = uni.getRecorderManager();
        //#endif
    };
    /**
     * @description 获取录音权限
     * @returns
     */
    const authorize = (): Promise<void> => {
        // eslint-disable-next-line no-async-promise-executor
        return new Promise(async (resolve, reject) => {
            //优化点：已经获取过权限，不必再获取
            // if (isAuth.value) {
            //     resolve()
            //     return
            // }

            //#ifdef H5
            mediaRecorder.value.open(
                () => {
                    // isAuth.value = true
                    resolve();
                },
                (msg: string) => {
                    reject("无法录音:" + msg);
                }
            );
            //#endif

            //#ifdef MP
            try {
                await uni.authorize({
                    scope: "scope.record",
                });
                resolve();
            } catch (error: any) {
                const res = await uni.showModal({
                    title: "开启麦克风权限",
                    content: "为了正常使用语音输入功能，请开启麦克风权限",
                    confirmText: "去设置",
                });
                if (res.confirm) {
                    const { authSetting } = await uni.openSetting();
                    if (authSetting["scope.record"]) {
                        // isAuth.value = true
                        return resolve();
                    }
                }
                reject("无法录音：用户拒绝了麦克风权限");
            }

            //#endif
        });
    };
    /**
     *
     * @param options
     * @returns
     * @description 开始录音
     */
    const start = async () => {
        // 注册事件
        try {
            if (!mediaRecorder.value) {
                createMediaRecorder();
            }

            mediaRecorder.value.start(options);
            isRecording.value = true;
            callbacks?.onstart?.();
            //#ifndef H5
            mediaRecorder.value.onStart(() => {
                isRecording.value = true;
                callbacks?.onstart?.();
            });
            mediaRecorder.value.onStop((e: any) => {
                isRecording.value = false;
                callbacks?.onstop?.(e);
            });
            mediaRecorder.value.onError((e: any) => {
                isRecording.value = false;
                callbacks?.onerror?.(e);
            });
            // 被打断结束，恢复录音
            mediaRecorder.value.onInterruptionEnd(() => {
                isRecording.value = true;
                mediaRecorder.value.resume();
            });
            mediaRecorder.value.onFrameRecorded(async ({ frameBuffer }: any) => {
                const decoder = Mp3.newDecoder(frameBuffer);
                if (decoder != null) {
                    const pcmArrayBuffer = decoder.decode();
                    const pcmArr = new Int16Array(pcmArrayBuffer);
                    const size = pcmArr.length;

                    let sum = 0;
                    for (let i = 0; i < size; i++) {
                        sum += Math.abs(pcmArr[i]);
                    }
                    let powerLevel = (sum * 500.0) / (size * 16383);
                    if (powerLevel >= 100) {
                        powerLevel = 100;
                    }
                    if (powerLevel <= 5) {
                        powerLevel = 1;
                    }

                    powerLevel = parseInt(String(powerLevel));
                    callbacks?.ondata?.({
                        pcmData: pcmArr,
                        powerLevel,
                        sampleRate: options?.sampleRate!,
                    });
                }
            });
            //#endif
        } catch (error) {
            console.log(error);
            return Promise.reject(error);
        }
    };
    const stop = () => {
        if (mediaRecorder.value) {
            //#ifndef H5
            mediaRecorder.value?.stop();
            //#endif
            //#ifdef H5
            mediaRecorder.value?.stop(
                (blob: Blob, duration: number) => {
                    const tempFilePath = window.URL.createObjectURL(blob);
                    isRecording.value = false;
                    return callbacks?.onstop?.({
                        tempFilePath,
                        duration,
                    });
                },
                () => {
                    isRecording.value = false;
                }
            );
            //#endif
        }
        //#ifndef H5
        mediaRecorder.value?.offStart?.();
        mediaRecorder.value?.offStop?.();
        mediaRecorder.value?.offError?.();
        mediaRecorder.value?.offInterruptionEnd?.();
        mediaRecorder.value?.onFrameRecorded?.();
        //#endif
    };

    const resume = () => {
        mediaRecorder.value?.resume();
    };

    const close = () => {
        mediaRecorder.value?.close?.(() => {
            isRecording.value = false;
        });
        mediaRecorder.value = null;
        // isAuth.value = false
    };

    const pause = () => {
        mediaRecorder.value?.pause();
    };

    onBeforeUnmount(() => {
        stop();
        close();
    });
    return {
        isRecording,
        mediaRecorder,
        start,
        authorize,
        stop,
        close,
        pause,
        resume,
    };
};

interface AudioGraphOptions {
    /**
     * @description 画布的canvasid
     */
    id: string;
    /**
     * @description 画布的宽度
     */
    width: number;
    /**
     * @description 画布的高度
     */
    height: number;
    /**
     * @description 缩放系数，应为正整数，使用2(3? no!)倍宽高进行绘制，避免移动端绘制模糊
     */
    scale: number;

    /**
     * @description 绘制帧率，不可过高
     */
    fps: number;
    /**
     * @description 频率傅立叶变换的大小，越大，也精细
     */
    fftSize: number;
    /**
     * @description 直方图柱子数量
     */
    lineCount: number;
    /**
     * @description 柱子最小高度
     */
    minHeight: number;
    /**
     * @description 柱子线条宽度占比，为所有柱子占用整个视图宽度的比例，剩下的空白区域均匀插入柱子中间；
     * 默认值也基本相当于一根柱子占0.6，一根空白占0.4；
     * 设为1不留空白，当视图不足容下所有柱子时也不留空白
     */
    widthRatio: number;
    /**
     * @description 柱子间空白固定基础宽度，柱子宽度自适应，当不为0时widthRatio无效，当视图不足容下所有柱子时将不会留空白，允许为负数，让柱子发生重叠
     */
    spaceWidth: number;

    /**
     * @description position: -1, //绘制位置，取值-1到1，-1为最底下，0为中间，1为最顶上，支持小数点
     */
    position: number;

    /**
     * @description 是否开启镜像
     */
    mirrorEnable: boolean;
    /**
     * @description 柱子从最顶上下降到最底部最长时间ms
     */
    fallDuration: number;
    /**
     * @description 柱子的颜色
     * @example [{pos:0,color:"#fff"},{pos:1,color"#000"}]
     */
    linear: { pos: number; color: string }[];
    /**
     * @description 是否开启圆角
     */
    round: boolean;
    /**
     * @description 是否要绘制所有频率；默认false主要绘制5khz以下的频率，高频部分占比很少，此时不同的采样率对频谱显示几乎没有影响；设为true后不同采样率下显示的频谱是不一样的，因为 最大频率=采样率/2 会有差异
            //当发生绘制时会回调此方法，参数为当前绘制的频率数据和采样率，可实现多个直方图同时绘制，只消耗一个input输入和计算时间
     */
    fullFreq: boolean;
    onDraw?(
        ctx: UniApp.CanvasContext,
        data: {
            frequencyData: Float64Array | null;
            sampleRate: number;
            options: AudioGraphOptions;
        }
    ): void;
}

export type AudioGraphUserOptions = Partial<AudioGraphOptions> & Pick<AudioGraphOptions, "id" | "width" | "height">;
const defaultAudioGraphOptions: AudioGraphOptions = {
    id: "",
    width: 0,
    height: 0,
    scale: 2,
    fps: 30,
    fftSize: 1024,
    lineCount: 6,
    widthRatio: 0.6,
    spaceWidth: 0,
    minHeight: 8,
    position: 0,
    mirrorEnable: false,
    fallDuration: 600,
    linear: [
        {
            pos: 0,
            color: "white",
        },
        {
            pos: 1,
            color: "white",
        },
    ],
    round: true,
    fullFreq: false,
};

export const useRenderAudioGraph = (options: AudioGraphUserOptions) => {
    const canvasId = options.id;
    if (!canvasId) {
        console.error("绘制图形前必须指定`canvasId`");
    }
    const instance = getCurrentInstance();
    const opt: AudioGraphOptions = Object.assign({}, defaultAudioGraphOptions, options);
    const canvasCtx = uni.createCanvasContext(canvasId, instance?.proxy);
    if (!opt.width || !opt.height) {
        console.error("必须指定画布的宽高");
    }

    const fft = genFFT(opt.fftSize);
    let fragment: DataResult | undefined = undefined;
    let pcmPos = 0;
    let inputTime = 0;
    let scheduleTimer = 0;
    let drawTime = 0;
    let lastH: number[] = [];
    const render = (data: DataResult) => {
        fragment = data;
        pcmPos = 0;
        inputTime = Date.now();
        schedule();
    };
    const genLinear = (ctx: UniApp.CanvasContext, colors: AudioGraphOptions["linear"], from: number, to: number) => {
        const rtv = ctx.createLinearGradient(0, from, 0, to);
        for (let i = 0; i < colors.length; i++) {
            rtv.addColorStop(colors[i].pos, colors[i].color);
        }
        return rtv;
    };

    const drawRect = (ctx: UniApp.CanvasContext, x: number, y: number, width: number, height: number, r: number[]) => {
        const [r1, r2, r3, r4] = r;
        ctx.beginPath();
        ctx.moveTo(x + r1, y);
        ctx.lineTo(x + width - r1, y);
        ctx.arc(x + width - r2, y + r2, r2, Math.PI * 1.5, Math.PI * 2);
        ctx.lineTo(x + width, y + height - r3);
        ctx.arc(x + width - r3, y + height - r3, r3, 0, Math.PI * 0.5);
        ctx.lineTo(x + r4, y + height);
        ctx.arc(x + r4, y + height - r4, r4, Math.PI * 0.5, Math.PI);
        ctx.lineTo(x, y + r1);
        ctx.arc(x + r1, y + r1, r1, Math.PI, Math.PI * 1.5);
        ctx.fill();
    };

    const onDraw: AudioGraphOptions["onDraw"] = opt.onDraw
        ? opt.onDraw
        : (ctx, { frequencyData, sampleRate, options }) => {
              const {
                  scale,
                  width,
                  height,
                  lineCount,
                  round,
                  fftSize,
                  position,
                  fallDuration,
                  fps,
                  fullFreq,
                  linear,
                  mirrorEnable,
              } = options;
              const realWidth = width * scale;
              const realHeight = height * scale;
              //计算高度位置
              const posAbs = Math.abs(position);
              let originY = position == 1 ? 0 : realHeight; //y轴原点
              let heightY = realHeight; //最高的一边高度
              if (posAbs < 1) {
                  heightY = heightY / 2;
                  originY = heightY;
                  heightY = Math.floor(heightY * (1 + posAbs));
                  originY = Math.floor(position > 0 ? originY * (1 - posAbs) : originY * (1 + posAbs));
              }
              const lastHeight = lastH;
              // 计算速度
              const speed = Math.ceil(heightY / (fallDuration / (1000 / fps)));
              const Y0 = 1 << (Math.round(Math.log(fftSize) / Math.log(2) + 3) << 1);
              const logY0 = Math.log(Y0) / Math.log(10);
              const dBmax = (20 * Math.log(0x7fff)) / Math.log(10);

              const fftSizeHalf = fftSize / 2.5;
              let fftSize5k = fftSizeHalf;
              //非绘制所有频率时，计算5khz所在位置，8000采样率及以下最高只有4khz
              if (!fullFreq) {
                  fftSize5k = Math.min(fftSizeHalf, Math.floor((fftSizeHalf * 5000) / (sampleRate / 2)));
              }

              const isFullFreq = fftSize5k == fftSize;
              const line80 = isFullFreq ? lineCount : Math.round(lineCount * 0.8); //80%的柱子位置
              const fftSizeStep1 = fftSize5k / line80;
              const fftSizeStep2 = isFullFreq ? 0 : (fftSizeHalf - fftSize5k) / (lineCount - line80);
              let fftIdx = 0;
              for (let i = 0; i < lineCount; i++) {
                  // !fullFreq 时不采用jmp123的非线性划分频段，录音语音并不适用于音乐的频率，应当弱化高频部分
                  //80%关注0-5khz主要人声部分 20%关注剩下的高频，这样不管什么采样率都能做到大部分频率显示一致。
                  const start = Math.ceil(fftIdx);
                  if (i < line80) {
                      //5khz以下
                      fftIdx += fftSizeStep1;
                  } else {
                      //5khz以上
                      fftIdx += fftSizeStep2;
                  }
                  let end = Math.ceil(fftIdx);
                  if (end == start) end++;
                  end = Math.min(end, fftSizeHalf);

                  //参考AudioGUI.java .drawHistogram方法

                  //查找当前频段的最大"幅值"
                  let maxAmp = 0;
                  if (frequencyData) {
                      for (let j = start; j < end; j++) {
                          maxAmp = Math.max(maxAmp, Math.abs(frequencyData[j]));
                      }
                  }

                  //计算音量
                  const dB = maxAmp > Y0 ? Math.floor((Math.log(maxAmp) / Math.log(10) - logY0) * 17) : 0;
                  let h = heightY * Math.min(dB / dBmax, 1);

                  //使柱子匀速下降
                  lastHeight[i] = (lastHeight[i] || 0) - speed;
                  if (h < lastHeight[i]) {
                      h = lastHeight[i];
                  }
                  if (h < 0) {
                      h = 0;
                  }
                  lastHeight[i] = h;
              }
              //开始绘制图形

              ctx.clearRect(0, 0, realWidth, realHeight);
              const d = 1 / opt.scale;
              ctx.scale(d, d);
              const linear1 = genLinear(ctx, linear, originY, originY - heightY); //上半部分的填充
              const linear2 = genLinear(ctx, linear, originY, originY + heightY); //下半部分的填充
              const mirrorCount = mirrorEnable ? lineCount * 2 - 1 : lineCount; //镜像柱子数量翻一倍-1根

              const spaceWidth = options.spaceWidth * scale;
              let widthRatio = options.widthRatio;
              if (spaceWidth != 0) {
                  widthRatio = (realWidth - spaceWidth * (mirrorCount + 1)) / realWidth;
              }

              let lineWN = 0,
                  spaceFloat = 0,
                  lineWF = 0;
              for (let i = 0; i < 2; i++) {
                  const lineFloat = Math.max(1 * scale, (realWidth * widthRatio) / mirrorCount); //柱子宽度至少1个单位
                  lineWN = Math.floor(lineFloat);
                  lineWF = lineFloat - lineWN; //提取出小数部分
                  spaceFloat = (realWidth - mirrorCount * lineFloat) / (mirrorCount + 1); //均匀间隔，首尾都留空，可能为负数，柱子将发生重叠
                  if (spaceFloat > 0 && spaceFloat < 1) {
                      widthRatio = 1;
                      spaceFloat = 0; //不够一个像素，丢弃不绘制间隔，重新计算
                  } else break;
              }
              //绘制
              const minHeight = options.minHeight * scale;
              const XFloat = mirrorEnable ? (realWidth - lineWN) / 2 - spaceFloat : 0; //镜像时，中间柱子位于正中心

              for (let iMirror = 0; iMirror < 2; iMirror++) {
                  if (iMirror) {
                      ctx.save();
                      ctx.scale(-1, 1);
                  }
                  const xMirror = iMirror ? realWidth : 0; //绘制镜像部分，不用drawImage(canvas)进行镜像绘制，提升兼容性（iOS微信小程序bug https://developers.weixin.qq.com/community/develop/doc/000aaca2148dc8a235a0fb8c66b000）

                  //绘制柱子

                  for (let i = 0, xFloat = XFloat, wFloat = 0, x, y, w, h; i < lineCount; i++) {
                      xFloat += spaceFloat;
                      x = Math.floor(xFloat) - xMirror;
                      w = lineWN;
                      wFloat += lineWF;
                      if (wFloat >= 1) {
                          w++;
                          wFloat--;
                      } //小数凑够1像素
                      h = Math.max(lastH[i], minHeight);
                      //   console.log(h)
                      const radius = round ? w / 2 : 0;
                      //绘制上半部分
                      let r = new Array(4).fill(radius);
                      if (originY != 0) {
                          y = originY - h;
                          ctx.setFillStyle(linear1);
                          if (originY != realHeight) {
                              r = [radius, radius, 0, 0];
                          }

                          drawRect(ctx, x, y, w, h, r);
                      }
                      //绘制下半部分
                      if (originY != realHeight) {
                          ctx.setFillStyle(linear2);
                          if (originY != 0) {
                              r = [0, 0, radius, radius];
                          }

                          drawRect(ctx, x, originY, w, h, r);
                      }

                      xFloat += w;
                  }
                  if (iMirror) {
                      ctx.restore();
                  }
                  if (!mirrorEnable) break;
              }
              ctx.draw();
          };
    delete opt.onDraw;
    const draw = (frequencyData: Float64Array | null, sampleRate: number) => {
        onDraw(canvasCtx, {
            frequencyData,
            sampleRate: sampleRate,
            options: opt,
        });
    };
    const schedule = () => {
        const interval = Math.floor(1000 / opt.fps);
        if (!scheduleTimer) {
            scheduleTimer = setInterval(function () {
                schedule();
            }, interval);
        }

        const now = Date.now();
        drawTime = drawTime || 0;
        if (now - inputTime > opt?.fallDuration * 1.5) {
            //超时没有输入，顶部横条已全部落下，干掉定时器
            clearInterval(scheduleTimer);
            lastH = []; //重置高度再绘制一次，避免定时不准没到底就停了
            draw(null, fragment!.sampleRate);
            return;
        }
        if (now - drawTime < interval) {
            //没到间隔时间，不绘制
            return;
        }
        drawTime = now;
        //调用FFT计算频率数据
        const bufferSize = fft.bufferSize;
        const pcm = fragment!.pcmData;
        let pos = pcmPos;
        const arr = new Int16Array(bufferSize);
        for (let i = 0; i < bufferSize && pos < pcm.length; i++, pos++) {
            arr[i] = pcm[pos];
        }
        pcmPos = pos;

        const frequencyData = fft.transform(arr);
        draw(frequencyData, fragment!.sampleRate);
    };

    const stopRender = () => {
        clearInterval(scheduleTimer);
    };
    return {
        render,
        draw,
        stopRender,
    };
};
