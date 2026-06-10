Page({
  data: {
    url: 'https://www.hbdxm.com/manager.html'
  },
  onLoad(options) {
    if (options.url) {
      this.setData({ url: decodeURIComponent(options.url) });
    }
  },
  onMessage(e) {
    console.log('网页消息:', e.detail.data);
  },
  onError(e) {
    wx.showToast({ title: '加载失败', icon: 'none' });
  }
});
