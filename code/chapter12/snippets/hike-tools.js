import { hoursAndMinutes } from './time.js';

Drupal.behaviors.lensTrailHikeTools = {
  attach() {
    console.log(hoursAndMinutes(140));
  },
};
