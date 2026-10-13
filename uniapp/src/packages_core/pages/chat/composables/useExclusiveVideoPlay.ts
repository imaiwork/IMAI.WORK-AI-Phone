/** 会话内多视频互斥播放：新视频起播时暂停上一个 */

let currentId = "";
let currentPause: (() => void) | null = null;

export function exclusiveVideoPlay(id: string, pause: () => void) {
    const key = String(id || "").trim();
    if (!key) return;
    if (currentId && currentId !== key && currentPause) {
        try {
            currentPause();
        } catch {
            /* ignore */
        }
    }
    currentId = key;
    currentPause = pause;
}

export function releaseExclusiveVideo(id: string) {
    const key = String(id || "").trim();
    if (currentId === key) {
        currentId = "";
        currentPause = null;
    }
}
