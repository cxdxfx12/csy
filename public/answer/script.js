var FAQS=[
{id:1,q:'怎么创建账单？',a:'进入 <b>收费管理 → 账单管理</b>，点击"新增账单"按钮，选择小区、楼栋、房间后填写收费项目即可生成账单。',icon:'💰',category:'charge',catName:'收费管理'},
{id:2,q:'如何添加业主？',a:'进入 <b>业主管理 → 业主管理</b>，点击"新增业主"，填写姓名、手机号、关联房间等信息后保存即可。业主也可以通过微信小程序自助注册。',icon:'👤',category:'property',catName:'业主房产'},
{id:3,q:'报修工单怎么处理？',a:'进入 <b>报修管理 → 工单管理</b>，可查看所有工单。点击工单可查看详情，指派维修人员、更新处理进度，完成后关闭工单。类型包括水、电、气、门窗、管道、家电等。',icon:'🔧',category:'repair',catName:'报修工单'},
{id:4,q:'门禁卡怎么发放？',a:'进入 <b>安防管理 → 门禁卡</b>，点击"新增门禁卡"，选择业主、绑定房间和门禁设备后保存。支持 IC 卡、人脸识别等多种认证方式。',icon:'🔑',category:'security',catName:'安防门禁'},
{id:5,q:'怎样配置支付方式？',a:'进入 <b>支付配置</b>，配置微信支付商户号和密钥。配置完成后业主即可通过微信小程序在线缴费。',icon:'💳',category:'other',catName:'其他操作'},
{id:6,q:'停车费率怎么设置？',a:'进入 <b>停车管理 → 停车费率</b>，新增费率规则，设置按小时/按天/封顶金额等。支持不同车型（临时车、月租车）差异化费率。',icon:'🚗',category:'parking',catName:'停车管理'},
{id:7,q:'如何查看财务报表？',a:'进入 <b>收费管理 → 财务流水</b>，可按时间段、小区、项目筛选查看收支明细，导出 Excel。还可查看欠费统计、缴费趋势图表。',icon:'📊',category:'charge',catName:'收费管理'},
{id:8,q:'怎么发公告通知？',a:'进入 <b>公告管理 → 公告列表</b>，点击"新增公告"，填写标题内容，选择发布范围后发布。业主将在微信小程序收到推送。',icon:'📢',category:'other',catName:'其他操作'},
{id:9,q:'如何录入房屋信息？',a:'先在 <b>小区管理</b> 创建小区，再创建楼栋和房间。每个房间可绑定业主、记录面积户型。支持批量导入。',icon:'🏠',category:'property',catName:'业主房产'},
{id:10,q:'车辆如何登记？',a:'进入 <b>停车管理 → 车辆管理</b>，点击"新增车辆"，输入车牌号、类型、品牌颜色，关联业主。登记后自动识别放行。',icon:'🚙',category:'parking',catName:'停车管理'},
{id:11,q:'员工档案怎么管理？',a:'进入 <b>员工档案</b>，添加员工信息包括姓名、手机号、部门职位。系统支持考勤排班、工资管理等联动。',icon:'👷',category:'security',catName:'安防门禁'},
{id:12,q:'供应商如何管理？',a:'进入 <b>供应商名录</b>，添加名称、联系人、联系方式、服务类别。可与采购订单、合同、评价联动使用。',icon:'🏪',category:'other',catName:'其他操作'}
];

var faqContainer=document.getElementById('faqContainer');
var searchInput=document.getElementById('searchInput'); var searchClear=document.getElementById('searchClear');
var categoryTabs=document.getElementById('categoryTabs'); var emptyResult=document.getElementById('emptyResult');
var totalFaqEl=document.getElementById('totalFaq'); var backTopBtn=document.getElementById('backTop');
var currentCategory='all'; var currentSearch='';

document.addEventListener('DOMContentLoaded',function(){animateCounter(totalFaqEl,FAQS.length);
  var hash=window.location.hash;if(hash&&hash.indexOf('#q=')===0){
    currentSearch=decodeURIComponent(hash.substring(3));searchInput.value=currentSearch;
    searchClear.style.display='flex';document.title='大圣知识库 - '+currentSearch;
  }renderFAQs();
});

function animateCounter(el,target){var c=0;var s=Math.max(1,Math.ceil(target/20));
  var t=setInterval(function(){c+=s;if(c>=target){c=target;clearInterval(t)}el.textContent=c},40);
}
function esc(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}

function renderFAQs(){
  var f=FAQS.filter(function(fq){var m1=currentCategory==='all'||fq.category===currentCategory;
    if(!currentSearch.trim())return m1;
    var s2=fq.q.toLowerCase().indexOf(currentSearch.toLowerCase())>=0||fq.a.toLowerCase().indexOf(currentSearch.toLowerCase())>=0;
    return m1&&(s2||fq.catName.indexOf(currentSearch)>=0);
  });
  if(f.length===0){faqContainer.innerHTML='';emptyResult.style.display='block';return;}
  emptyResult.style.display='none';
  faqContainer.innerHTML=f.map(function(item,i){
    return '<div class="faq-card cat-'+item.category+'" data-id="'+item.id+'" style="animation-delay:'+i*0.06+'s">'+
      '<div class="faq-header" onclick="toggleFaq('+item.id+')">'+
        '<div class="faq-icon-wrap">'+item.icon+'</div>'+
        '<div class="faq-body-area"><div class="faq-q">'+esc(item.q)+'</div><span class="faq-cat-tag">'+item.catName+'</span></div>'+
        '<div class="fqa-rrow"><svg viewBox="0 0 24 24"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z"/></svg></div>'+
      '</div><div class="faq-answer" id="answer-'+item.id+'"><div class="faq-answer-inner">'+item.a+'</div></div></div>';
  }).join('');
}

window.toggleFaq=function(id){
  var card=document.querySelector('.faq-card[data-id="'+id+'"]');var ans=document.getElementById('answer-'+id);
  if(!card||!ans)return;
  var isOpen=card.classList.contains('open');
  document.querySelectorAll('.faq-card.open').forEach(function(c){c.classList.remove('open');var a=c.querySelector('.faq-answer');if(a)a.style.maxHeight='0'});
  if(!isOpen){card.classList.add('open');ans.style.maxHeight=(ans.scrollHeight+40)+'px';
    if(window.innerWidth<=640)setTimeout(function(){card.scrollIntoView({behavior:'smooth',block:'center'})},150);
  }
};

searchInput.addEventListener('input',function(){current=searchInput.value;searchClear.style.display=current?'flex':'none';renderFAQs()});
searchClear.addEventListener('click',function(){searchInput.value='';current='';searchClear.style.display='none';renderFAQs();searchInput.focus()});
document.querySelectorAll('.hint-tag').forEach(function(t){t.addEventListener('click',function(){
  var kw=t.dataset.keyword;searchInput.value=kw;current=kw;searchClear.style.display='flex';renderFAQs()})});
categoryTags.addEventListener?null:null;
categoryTabs.addEventListener('click',function(e){var tab=e.target.closest('.cat-tab');if(!tab)return;
  document.querySelectorAll('.cat-tab').forEach(function(t){t.classList.remove('active')});
  tab.classList.add('active');currentCategory=tab.dataset.category;renderFAQs()});
window.addEventListener('scroll',function(){backTopBtn.classList.toggle('visible',window.scrollY>400)});
backTopBtn.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'})});
