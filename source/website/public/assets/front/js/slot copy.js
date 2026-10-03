

$(document).ready(function () {
  "use strict"
  let requestSeatIds = $("#seatIds").val();
  let requestSeatData = $("#seatData").val();
  var seatIds = requestSeatIds != '' ? JSON.parse(requestSeatIds) : [];
  var seatData = requestSeatData != '' ? JSON.parse(requestSeatData) : [];
  var selectedBox = null;
  var event_id = null;
  var ticket_id = null;
  var slot_unique_id = null;

  function boxes(event_slots) {

    const img = document.getElementById('target-image');


    var boxes = JSON.parse(event_slots);
    boxes.forEach(function (box) {

      let {
        pos_x,
        pos_y,
        width,
        height,
        slot_name,
        price,
        id,
        is_booked,
        rotate,
        background_color,
        slot_type,
        number_of_seat,
        filtered_seats,
        slot_unique_id,
        font_size,
        round,
        pricing_type
      } = box;

      drawBox(
        pos_x,
        pos_y,
        width,
        height,
        slot_name,
        price,
        id,
        is_booked,
        rotate,
        background_color,
        slot_type,
        number_of_seat,
        filtered_seats,
        slot_unique_id,
        font_size,
        round,
        pricing_type
      );
    });
  }

  function drawBox(pos_x, pos_y, width, height, slot_name = "", price =
    "", slot_id = null, slot_booked = null, slot_rotate, slot_background_colour, slot_type,
    number_of_seat, seats, slot_unique_id, font_size, slot_round, pricing_type) {
    var boxClasses =
      `box ${slot_booked == 1 ? ' disable_class' : ''} ${seatIds.includes(slot_id) ? 'box_label_check' : ''} slot`;
    var pos_x = pos_x - 0;
    var pos_y = pos_y - 0;

    var box = $(`<div class="${boxClasses}" data-slot_id="${slot_id}">`)
      .css({
        left: pos_x,
        top: pos_y,
        width: width,
        height: height,
        rotate: slot_rotate + 'deg',
        background: slot_background_colour,
        borderColor: darkenColor(slot_background_colour, 30),
        color: darkenColor(slot_background_colour, 30),
        borderRadius: slot_round + '%'
      })
      .data('slot_id', slot_id)
      .data('width', width)
      .data('height', height)
      .data('slot_name', slot_name)
      .data('price', price)
      .data('slot_booked', slot_booked)
      .data('slot_rotate', slot_rotate)
      .data('slot_background_colour', slot_background_colour)
      .data('slot_type', slot_type)
      .data('number_of_seat', number_of_seat)
      .data('seats', seats)
      .data('event_id', event_id)
      .data('ticket_id', ticket_id)
      .data('slot_unique_id', slot_unique_id)
      .data('pricing_type', pricing_type)
      .appendTo('#seatMap');

    var label = $(
      '<div class="box-label text-center" data-rotate-number="' +
      slot_rotate + '"><span style="rotate:-' + slot_rotate + 'deg">' + slot_name +
      ' </span></div>'
    )
      .css({
        width: width - 3,
        height: height - 3,
        fontSize: font_size + 'px'
      })
      .appendTo(box);
    //click
    box.click(function () {
      selectedBox = box;
      let width_v = selectedBox.data('width');
      let height_v = selectedBox.data('height');
      let price_v = selectedBox.data('price');
      let slot_id_v = selectedBox.data('slot_id');
      let slot_name_v = selectedBox.data('slot_name');
      let slot_rotate_v = selectedBox.data('slot_rotate');
      let slot_background_colour_v = selectedBox.data('slot_background_colour');
      let slot_booked = selectedBox.data('slot_booked');
      let slot_type_v = selectedBox.data('slot_type');
      let number_of_seat_v = selectedBox.data('number_of_seat');
      let seats_v = selectedBox.data('seats');
      let event_id = selectedBox.data('event_id');
      let ticket_id = selectedBox.data('ticket_id');
      let slot_unique_id = selectedBox.data('slot_unique_id');
      let pricing_type = selectedBox.data('pricing_type');

      if (slot_booked == 1) {
        toastr['error'](slot_already_booked_msg);
      } else {
        if (slot_type_v == 2) {
          let now_selected_seat = _.size(seatData);
          seats_v.forEach(seat => {
            if (!seatIds.includes(seat.id) && seat.seat_type == 2) {
              seatIds.push(seat.id);
              seatData.push({
                id: seat.id,
                name: seat.name,
                price: parseFloat(seat.price),
                payable_price: parseFloat(seat.payable_price),
                discount: parseFloat(seat.price) - parseFloat(seat.payable_price),
                s_type: seat.seat_type,
                slot_id: slot_id_v,
                slot_name: slot_name_v,
                event_id: parseInt(event_id),
                ticket_id: parseInt(ticket_id),
                slot_unique_id: parseInt(slot_unique_id)
              });

            } else {
              seatIds = seatIds.filter(id => id !== seat.id);
              seatData = seatData.filter(seat => seat.slot_id !== slot_id_v);
            }
          });
          if (_.size(seatData) > now_selected_seat) {
            toastr.success(seat_has_been_selected_msg);
          } else {
            toastr.warning(seat_has_been_unselected_msg);
          }

        } else {
          showPopupForm(
            pos_x,
            pos_y,
            width_v,
            height_v,
            slot_name_v,
            price_v,
            slot_id_v,
            slot_rotate_v,
            slot_background_colour_v,
            slot_type_v,
            number_of_seat_v,
            seats_v,
            event_id,
            ticket_id,
            slot_unique_id,
            pricing_type
          );
        }
      }
      slotSeatSelected();
    });
    slotSeatSelected();

  }

  function darkenColor(hex, percent) {
    let num = parseInt(hex.slice(1), 16),
      amt = Math.round(2.55 * percent),
      R = (num >> 16) - amt,
      G = (num >> 8 & 0x00FF) - amt,
      B = (num & 0x0000FF) - amt;

    return "#" + (
      0x1000000 +
      (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
      (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 +
      (B < 255 ? (B < 1 ? 0 : B) : 255)
    ).toString(16).slice(1);
  }

  function showPopupForm(
    pos_x,
    pos_y,
    width,
    height,
    slot_name,
    price,
    slot_id,
    slot_rotate_v,
    slot_background_color_v,
    slot_type_v,
    number_of_seat_v,
    seats_v,
    event_id,
    ticket_id,
    slot_unique_id,
    pricing_type
  ) {
    $("#slot_seat_modal").modal("show");
    $("#type_one_seat_show").html();
    let xml = showSeatList(seats_v, slot_id, slot_name, event_id, ticket_id, slot_unique_id, pricing_type);
    $("#type_one_seat_show").empty().append(xml);
  }

  $(document).on('click', '.btn_seat_mapping_slot', function (e) {
    e.preventDefault();
    slot_unique_id = parseInt($(this).attr('data-slot_unique_id'));
    event_id = parseInt($(this).attr('data-event_id'));
    ticket_id = parseInt($(this).attr('data-ticket_id'))

    let selectedBookingSeatIds = $("#seatIds").val();
    let selectedSeatData = $("#seatData").val();

    seatIds = selectedBookingSeatIds != '' ? JSON.parse(selectedBookingSeatIds) : [];
    seatData = selectedSeatData != '' ? JSON.parse(selectedSeatData) : [];

    let url = $(this).attr('data-url');
    $('.request-loader').addClass('show');

    $.ajax({
      url: url,
      method: 'GET',
      data: {
        slot_unique_id: slot_unique_id,
        ticket_id: ticket_id,
        event_id: event_id,
      },
      contentType: false,
      processData: true,
      success: function (data) {
        $('.request-loader').removeClass('show');
        if (data.status == "success") {
          $("#seatMappingModal").modal("show");
          $("#sloat_map_image").html(data.view);
          boxes(data.slots)
          setTimeout(function () {
            dragActive();
          }, 1000);
        }
        if (data.status == "error") {
          toastr.warning(data.message);
        }
      }

    });
  });

  function showSeatList(seats, slot_id, slot_name, event_id, ticket_id, slot_unique_id, pricing_type) {
    let xhtml = ``;
    seats.map(seat => {
      xhtml +=
        `<li class="list-group-item d-flex justify-content-between align-items-center"> ${seat.name}
                        ${ pricing_type != 'free' ? `<span class="price font-weight-bold">
                             ${price_text} ${currency_symbol}${parseFloat(seat.payable_price).toFixed(2)}
                              <del class="${seat.payable_price == seat.price ? 'd-none' : ''}">${currency_symbol}${parseFloat(seat.price).toFixed(2)}</del>
                             </span>`: `<span class="price font-weight-bold">
                                Free
                             </span>` }

                          <button class="float-right btn btn-primary ${seat.is_booked == 1 ? 'booked_seat' : 'add-seat-btn'} py-1 ${seatIds.includes(seat.id) ? 'bg-gray' : ''}"
                            data-seat_id="${seat.id}"
                            data-seat_name="${seat.name}"
                            data-seat_price="${parseFloat(seat.price)}"
                            data-payable_price="${parseFloat(seat.payable_price)}"
                            data-seat_type="${seat.seat_type}"
                            data-slot_id="${slot_id}"
                            data-slot_name="${slot_name}"
                            data-event_id="${event_id}"
                            data-ticket_id="${ticket_id}"
                            data-slot_unique_id="${slot_unique_id}">
                            ${seat.is_booked == 1 ? booked_text : seatIds.includes(seat.id) ? seleted_text : select_text}

                          </button>
                         </li>`
    });
    return xhtml;
  }

  $("body").on('click', '.add-seat-btn', function ($e) {
    $('.request-loader').addClass('show');

    let slot_id = $(this).data('slot_id');
    let seat_name = $(this).data('seat_name');
    let seat_id = $(this).data('seat_id');
    let seat_price = $(this).data('seat_price');
    let seat_type = $(this).data('seat_type');
    let slot_name_v = $(this).data('seat_type');
    let payable_price = $(this).data('payable_price');
    let event_id = $(this).data('event_id');
    let ticket_id = $(this).data('ticket_id');
    let slot_unique_id = $(this).data('slot_unique_id');

    if (!seatIds.includes(seat_id)) {
      seatIds.push(seat_id);
      seatData.push({
        id: seat_id,
        name: seat_name,
        price: parseFloat(seat_price),
        payable_price: parseFloat(payable_price),
        discount: parseFloat(seat_price) - parseFloat(payable_price),
        s_type: seat_type,
        slot_id: slot_id,
        slot_name: slot_name_v,
        event_id: parseInt(event_id),
        ticket_id: parseInt(ticket_id),
        slot_unique_id: parseInt(slot_unique_id)
      });

      $(this).addClass('bg-gray');
      $(this).html(seleted_text);
      toastr.success(seat_has_been_selected_msg);

    } else {
      seatIds = seatIds.filter(id => id !== seat_id);
      seatData = seatData.filter(seat => seat.id !== seat_id);
      $(this).removeClass('bg-gray');
      $(this).html(select_text);
      toastr.warning(seat_has_been_unselected_msg);
    }

    slotSeatSelected();
    $('.request-loader').removeClass('show');

  });

  $("body").on('click', '.remove_seat_btn', function ($e) {
    $('.request-loader').addClass('show');
    let seat_id = $(this).data('seat_id');
    if (seatIds.includes(seat_id)) {
      seatIds = seatIds.filter(id => id !== seat_id);
      seatData = seatData.filter(seat => seat.id !== seat_id);
      toastr.warning(seat_has_been_unselected_msg);
    }
    $("#slot_seat_modal").modal('hide');
    $('.request-loader').removeClass('show');
    slotSeatSelected();
  });

  function slotSeatSelected() {
    let boxs = document.querySelectorAll('.box');
    for (let i = 0; i < boxs.length; i++) {
      let slot_id = boxs[i].getAttribute('data-slot_id');
      let found = false; // flag
      seatData.forEach(seat => {
        if (seat.slot_id == slot_id) {
          found = true;
        }
      });
      if (found) {
        boxs[i].classList.add("box_label_check");
      } else {
        boxs[i].classList.remove("box_label_check");
      }
    }
    let selectedSeatCollectionbyUniqueId = _.filter(seatData, {
      slot_unique_id: slot_unique_id
    });
    let xhtml = ``;
    if (_.size(selectedSeatCollectionbyUniqueId) > 0) {
      xhtml = _.map(selectedSeatCollectionbyUniqueId, (seat) => `
                      <span class="badge badge-dark py-2 px-3 mx-1 my-1" style="font-size:14px">
                          ${seat.name}
                          ${seat.s_type == 1
          ? `<span class="pl-2 seat_data remove_seat_btn" data-seat_id="${seat.id}" style="cursor:pointer!important; color:red">
                                                                                    <i class="fas fa-times"></i>
                                                                                  </span>`
          : ``}
                      </span>`).join('');
    } else {
      xhtml = `${lang_No_seat_selected_yet}`;
    }


    $("#selected_sloat").empty().append(xhtml);
    //show price
    seatPriceShow(slot_unique_id);
  }

  function seatPriceShow(slot_unique_id) {
    localStorage.clear();
    let total_price = _.sumBy(
      _.filter(seatData, {
        slot_unique_id: slot_unique_id
      }),
      seat => parseFloat(seat.payable_price)
    );

    // localStorage
    localStorage.setItem('event_id', event_id);
    localStorage.setItem('ticket_id', ticket_id);
    localStorage.setItem('slot_unique_id', slot_unique_id);



    localStorage.setItem('seatIds', JSON.stringify(seatIds));
    localStorage.setItem('seatData', JSON.stringify(seatData));
    localStorage.setItem('seat_price', total_price.toFixed(2));


    // UI update
    $("#seatIds").val(localStorage.getItem(seatIds));
    $("#seatData").val(localStorage.getItem(seatData));
    $("#seat_price").empty().text(`${currency_symbol}${total_price.toFixed(2)}`);

    $("#seatIds").val(localStorage.getItem('seatIds'));
    $("#seatData").val(localStorage.getItem('seatData'))
    calcTotal();
  }

  $("body").on("click", ".close_slot_modal", function (e) {
    $("#slot_seat_modal").modal('hide');
    $("#seatMappingModal").modal('hide');
  });

  let zoomLevel = 1;
  document.getElementById("zoomIn").onclick = () => {
    const seatMap = document.getElementById("seatMap");
    zoomLevel += 0.1;
    seatMap.style.transform = `scale(${zoomLevel})`;
  };

  document.getElementById("zoomOut").onclick = () => {
    const seatMap = document.getElementById("seatMap");
    zoomLevel = Math.max(0.5, zoomLevel - 0.1);
    seatMap.style.transform = `scale(${zoomLevel})`;
  };

  document.getElementById("resetZoom").onclick = () => {
    zoomLevel = 1;
    seatMap.style.transform = `scale(1)`;
  };

  function dragActive() {
    const container = document.getElementById('seat-map-wrapper');
    const imageWrap = document.getElementById('seatMap');
    const addBtn = document.getElementById('add');
    const resetBtn = document.getElementById('reset');

    let startX, startY;
    let dragStartX, dragStartY;
    let isDragging = false;

    function getSlots() {
      return Array.from(imageWrap.querySelectorAll('.slot'));
    }


    function getTranslate() {
      const style = window.getComputedStyle(imageWrap);
      const matrix = new DOMMatrixReadOnly(style.transform);
      return {
        x: matrix.m41,
        y: matrix.m42
      };
    }

    function setTranslate(x, y) {
      imageWrap.style.transform = `translate(${x}px, ${y}px)`;
    }

    // Mouse events
    imageWrap.addEventListener('mousedown', function (e) {
      isDragging = true;
      const pos = getTranslate();
      dragStartX = e.clientX - pos.x;
      dragStartY = e.clientY - pos.y;
      e.preventDefault();
    });

    document.addEventListener('mousemove', function (e) {
      if (!isDragging) return;

      const x = e.clientX - dragStartX;
      const y = e.clientY - dragStartY;
      setTranslate(x, y);
      e.preventDefault();
    });

    document.addEventListener('mouseup', function () {
      isDragging = false;
    });

    // Touch events
    imageWrap.addEventListener('touchstart', function (e) {
      if (e.touches.length === 1) {
        isDragging = true;
        const touch = e.touches[0];
        const pos = getTranslate();
        dragStartX = touch.clientX - pos.x;
        dragStartY = touch.clientY - pos.y;
      }
    });

    document.addEventListener('touchmove', function (e) {
      if (!isDragging) return;
      if (e.touches.length === 1) {
        const touch = e.touches[0];
        const x = touch.clientX - dragStartX;
        const y = touch.clientY - dragStartY;
        setTranslate(x, y);
        e.preventDefault();
      }
    });

    document.addEventListener('touchend', function () {
      isDragging = false;
    });
  };

});

