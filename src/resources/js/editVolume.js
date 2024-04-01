$(document).ready(function () {
  const $scalewayAccessKeyIdInput = $('.scaleway-key-id');
  const $scalewaySecretAccessKeyInput = $('.scaleway-secret-key');
  const $bucketRegion = $('.scaleway-region');
  const $scalewayBucketSelect = $('.s3-bucket-select > select');
  const $scalewayRefreshBucketsBtn = $('.s3-refresh-buckets');
  const $scalewayRefreshBucketsSpinner = $scalewayRefreshBucketsBtn.parent().next().children();
  const $manualBucket = $('.s3-manualBucket');
  const $fsUrl = $('.fs-url');
  const $hasUrls = $('input[name=hasUrls]');
  let refreshingS3Buckets = false;

  $scalewayRefreshBucketsBtn.click(function() {
    if ($scalewayRefreshBucketsBtn.hasClass('disabled')) {
      return;
    }

    $scalewayRefreshBucketsBtn.addClass('disabled');
    $scalewayRefreshBucketsSpinner.removeClass('hidden');

    const data = {
      keyId: $scalewayAccessKeyIdInput.val(),
      secret: $scalewaySecretAccessKeyInput.val(),
      region: $bucketRegion.val(),
    };

    Craft.sendActionRequest('POST', 'scaleway-object-storage/buckets/load-bucket-data', {data})
      .then(({data}) => {
        if (!data.buckets.length) {
          return;
        }
        //
        const currentBucket = $scalewayBucketSelect.val();
        let currentBucketStillExists = false;

        refreshingS3Buckets = true;

        $scalewayBucketSelect.prop('readonly', false).empty();

        for (let i = 0; i < data.buckets.length; i++) {
          if (data.buckets[i].bucket == currentBucket) {
            currentBucketStillExists = true;
          }

          $scalewayBucketSelect.append('<option value="' + data.buckets[i].bucket + '" data-url-prefix="' + data.buckets[i].urlPrefix + '">' + data.buckets[i].bucket + '</option>');
        }

        if (currentBucketStillExists) {
          $scalewayBucketSelect.val(currentBucket);
        }

        refreshingS3Buckets = false;

        if (!currentBucketStillExists) {
          $scalewayBucketSelect.trigger('change');
        }
      })
      .catch(({response}) => {
        alert(response.data.message);
      })
      .finally(() => {
        $scalewayRefreshBucketsBtn.removeClass('disabled');
        $scalewayRefreshBucketsSpinner.addClass('hidden');
      });
  });

  $scalewayBucketSelect.change(function() {
    if (refreshingS3Buckets) {
      return;
    }

    const $selectedOption = $scalewayBucketSelect.children('option:selected');

    $fsUrl.val($selectedOption.data('url-prefix'));
  });

  const s3ChangeExpiryValue = function() {
    const parent = $(this).parents('.field');
    const amount = parent.find('.s3-expires-amount').val();
    const period = parent.find('.s3-expires-period select').val();

    const combinedValue = (parseInt(amount, 10) === 0 || period.length === 0) ? '' : amount + ' ' + period;

    parent.find('[type=hidden]').val(combinedValue);
  };

  $('.s3-expires-amount').keyup(s3ChangeExpiryValue).change(s3ChangeExpiryValue);
  $('.s3-expires-period select').change(s3ChangeExpiryValue);
});
