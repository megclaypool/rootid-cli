module.exports = async (page, scenario) => {
  var rainbow = String.fromCodePoint(0x1f308);
  var complexInteraction = scenario.complexInteraction;

  if (
    complexInteraction !== null &&
    complexInteraction !== "" &&
    typeof complexInteraction == "object" &&
    complexInteraction.length > 0
  ) {
    for (interaction of complexInteraction) {
      if (interaction.type == "await") {
        await page.waitFor(interaction.selector);
        console.log("Awaited " + interaction.selector);
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "awaitVisible") {
        await page.waitForSelector(interaction.selector, { visible: true });
        console.log(
          "Waited for " + interaction.selector + " to become visible"
        );
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "awaitHidden") {
        await page.waitForSelector(interaction.selector, { hidden: true });
        console.log("Waited for " + interaction.selector + " to become hidden");
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "awaitBackgroundImage") {
        // Note that broken images will count as complete, so the process won't hang indefinitely. However, it will wait until even huuuuuuge images finish rendering (or the process times out...)
        // In order to use await inside of it, the function inside page.evaluate needs to be asynchronous
        await page.evaluate(async (interaction) => {
          function rafAsync() {
            return new Promise((resolve) => {
              requestAnimationFrame(resolve); //faster than set time out
            });
          }

          function checkComplete(image) {
            if (image.complete !== true) {
              return rafAsync().then(() => checkComplete(image));
            } else {
              console.log(
                "Awaited the background image of the " +
                  interaction.selector +
                  " element. (Either it finished loading or it's broken...)"
              );
              return Promise.resolve(true);
            }
          }

          var element = document.querySelector(interaction.selector);
          var style =
            element.currentStyle || window.getComputedStyle(element, false);
          var url = style.backgroundImage.slice(4, -1).replace(/['"]/g, "");
          var img = new Image();
          img.src = url;

          // Note that broken qualifies as complete. *But* no un- or partially-loaded background images!
          await checkComplete(img);
        }, interaction);
      }

      if (interaction.type == "hover") {
        await page.waitFor(interaction.selector);
        await page.hover(interaction.selector);
        console.log("  Hovering over " + interaction.selector);
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "click") {
        await page.waitFor(interaction.selector);
        await page.evaluate((interaction) => {
          document.querySelector(interaction.selector).click();
        }, interaction);
        console.log("Clicked on " + interaction.selector);
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "keypress") {
        await page.waitFor(interaction.selector);
        await page.type(interaction.selector, interaction.keyPress);
        console.log(
          "  Typing " + interaction.keyPress + " in " + interaction.selector
        );
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "scroll") {
        var selector = interaction.selector;
        await page.waitFor(selector);
        await page.evaluate((selector) => {
          document.querySelector(selector).scrollIntoView();
        }, selector);
        console.log("  Scrolled to " + selector);
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "disableScrollReveal") {
        await page.evaluate(() => {
          if (typeof ScrollReveal !== "undefined") {
            ScrollReveal().destroy();
          }
        });
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "disableLazyload") {
        await page.evaluate(() => {
          if (
            typeof Drupal !== "undefined" &&
            typeof Drupal.blazy !== "undefined"
          ) {
            // Force immediate load of Drupal blazy lazyload images
            Drupal.blazy.init.load(
              document.getElementsByClassName("b-lazy", true)
            );
          }
          // Force immediate load of WP Autoptimize lazyload images
          if (
            typeof lazySizes !== "undefined" &&
            typeof lazySizes.loader !== "undefined" &&
            typeof lazySizes.loader.unveil !== undefined
          ) {
            var imageArray = document.querySelectorAll(".lazyload");
            imageArray.forEach((image) => lazySizes.loader.unveil(image));
          }
        });
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "pauseSlick") {
        await page.evaluate(() => {
          if (
            typeof jQuery !== "undefined" &&
            typeof jQuery.fn.slick !== "undefined"
          ) {
            var sliders = jQuery(".slick-slider");
            sliders.slick("slickPause");
            // start would be sliders.slick("slickPlay");
          }
        });
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "pauseOwl") {
        await page.evaluate(() => {
          if (
            typeof jQuery !== "undefined" &&
            typeof jQuery.fn.owlCarousel !== "undefined"
          ) {
            var sliders = jQuery(".owl-carousel");
            sliders.trigger("stop.owl.autoplay");
            // start would be sliders.trigger('play.owl.autoplay');
          }
        });
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "applyJQueryCode") {
        await page.evaluate((interaction) => {
          if (typeof jQuery !== "undefined") {
            var elements = jQuery(interaction.selector);
            if (interaction.expand == true) {
              eval("elements." + interaction.code);
            } else {
              var element = jQuery(elements[0]);
              // The internet agrees -- using eval is "killing kittens" level bad :P
              // But... it works :shrug:
              eval("element." + interaction.code);
            }
          }
        }, interaction);
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }

      if (interaction.type == "applyVanillaCode") {
        await page.evaluate((interaction) => {
          if (interaction.expand == true) {
            var elements = document.querySelectorAll(interaction.selector);
            elements.forEach(function (element) {
              // The internet agrees -- using eval is "killing kittens" level bad :P
              // But... it works :shrug:
              eval("element." + interaction.code);
            }, interaction);
          } else {
            element = document.querySelector(interaction.selector);
            eval("element." + interaction.code);
          }
        }, interaction);
        if (interaction.wait) {
          await page.waitFor(interaction.wait);
        }
      }
    }
  }
};
