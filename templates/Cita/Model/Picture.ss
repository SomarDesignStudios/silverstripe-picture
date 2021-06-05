<figure>
  <picture>
      <% if $Phone %>
          <% if $Dimensions.sm %>
          <source
              srcset="{$Phone.FitMax($Dimensions.sm.Default.Width,$Dimensions.sm.Default.Height).URL},
                  {$Phone.FitMax($Dimensions.sm.Retina.Width,$Dimensions.sm.Retina.Height).URL} 2x"
              media="(max-width: 575px)"
          >
          <% else %>
          <source srcset="{$Phone.URL}" media="(max-width: 575px)">
          <% end_if %>
      <% end_if %>
      <% if $Tablet %>
          <% if $Dimensions.md %>
          <source
              srcset="{$Tablet.FitMax($Dimensions.md.Default.Width,$Dimensions.md.Default.Height).URL},
                  {$Tablet.FitMax($Dimensions.md.Retina.Width,$Dimensions.md.Retina.Height).URL} 2x"
              media="(min-width: 576px) and (max-width: 991px)"
          >
          <% else %>
          <source srcset="{$Tablet.URL}" media="(min-width: 576px) and (max-width: 991px)">
          <% end_if %>
      <% end_if %>
      <% if $Dimensions.lg %>
          <img<% if $ExtraClass %> class="$ExtraClass"<% end_if %> src="{$Desktop.FitMax($Dimensions.lg.Default.Width,$Dimensions.lg.Default.Height).URL}" alt="{$Title}"/>
      <% else %>
          <img<% if $ExtraClass %> class="$ExtraClass"<% end_if %> src="{$Desktop.URL}" alt="{$Title}"/>
      <% end_if %>
  </picture>
  <% if $Caption %>
  <figcaption>$Caption</figcaption>
  <% end_if %>
</figure>
