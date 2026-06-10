Page({
  data: {
    url: 'https://www.hbdxm.com/'
  },
  onLoad(options) {
    if (options.url) {
      this.setData({ url: decodeURIComponent(options.url) });
    }
  },
  onMessage(e) {
    console.log('网页消息:', e.detail.data);
  },
  onLoad(e) {
    console.log('页面加载完成');
  },
  onError(e) {
    wx.showToast({ title: '加载失败', icon: 'none' });
    console.error('加载错误:', e.detail);
  }
});
