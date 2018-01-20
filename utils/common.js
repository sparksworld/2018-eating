function showLoading(status) {
	if(status) {
		wx.showLoading({
	        title: '加载中',
	    })
	    return
	}
	if(!status) {
        wx.hideLoading()
	}   
}
module.exports = {
	showLoading: showLoading
}