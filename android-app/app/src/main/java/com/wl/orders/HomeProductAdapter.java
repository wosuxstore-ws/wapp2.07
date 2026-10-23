package com.wl.orders;

import android.content.res.ColorStateList;
import android.graphics.drawable.GradientDrawable;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.ColorRes;
import androidx.annotation.DrawableRes;
import androidx.annotation.NonNull;
import androidx.core.content.ContextCompat;
import androidx.core.widget.ImageViewCompat;
import androidx.recyclerview.widget.RecyclerView;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

// 2-column product tiles on the Home screen. The tiles come from get_products.php:
// HomeActivity calls tilesFromJson(...) on the response and then setTiles(...).
public class HomeProductAdapter extends RecyclerView.Adapter<HomeProductAdapter.ViewHolder> {

    public static class Tile {
        final int id;
        final String name;
        final String price;
        @DrawableRes final int icon;
        @ColorRes final int bg;
        @ColorRes final int fg;

        public Tile(int id, String name, String price, @DrawableRes int icon, @ColorRes int bg, @ColorRes int fg) {
            this.id = id;
            this.name = name;
            this.price = price;
            this.icon = icon;
            this.bg = bg;
            this.fg = fg;
        }
    }

    // Tile colors repeat in this order: blue, peach, green, lilac, blue, ...
    private static final @ColorRes int[][] TILE_COLORS = {
            {R.color.tile_blue_bg,  R.color.tile_blue_fg},
            {R.color.tile_peach_bg, R.color.tile_peach_fg},
            {R.color.tile_green_bg, R.color.tile_green_fg},
            {R.color.tile_lilac_bg, R.color.tile_lilac_fg},
    };

    // get_products.php "products" array -> tiles (at most `max`).
    public static List<Tile> tilesFromJson(JSONArray array, int max) throws JSONException {
        List<Tile> tiles = new ArrayList<>();
        for (int i = 0; i < array.length() && i < max; i++) {
            JSONObject p = array.getJSONObject(i);
            int[] colors = TILE_COLORS[i % TILE_COLORS.length];
            tiles.add(new Tile(
                    p.optInt("id"),
                    Format.str(p, "title"),
                    Format.moneyFixed(Format.str(p, "price")),
                    iconFor(Format.str(p, "category")),
                    colors[0],
                    colors[1]));
        }
        return tiles;
    }

    // Picks one of the existing icons from the product's category name.
    @DrawableRes
    static int iconFor(String category) {
        String c = category == null ? "" : category.toLowerCase(Locale.US);
        if (c.contains("mouse"))                                          return R.drawable.ic_mouse;
        if (c.contains("earbud") || c.contains("handfree")
                || c.contains("handsfree"))                               return R.drawable.ic_handsfree;
        if (c.contains("headphone"))                                      return R.drawable.ic_headphones;
        if (c.contains("cable") || c.contains("usb"))                     return R.drawable.ic_cable;
        if (c.contains("connector"))                                      return R.drawable.ic_connector;
        if (c.contains("charger"))                                        return R.drawable.ic_plug;
        if (c.contains("power"))                                          return R.drawable.ic_powerbank;
        if (c.contains("speaker"))                                        return R.drawable.ic_speaker;
        if (c.contains("game"))                                           return R.drawable.ic_gamepad;
        if (c.contains("modem") || c.contains("network"))                 return R.drawable.ic_network;
        return R.drawable.ic_bag; // anything else
    }

    private List<Tile> tiles = new ArrayList<>();

    public void setTiles(List<Tile> newTiles) {
        this.tiles = newTiles;
        notifyDataSetChanged();
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_home_product, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder h, int position) {
        Tile t = tiles.get(position);

        // Each tile needs its own background instance so colors don't bleed between tiles.
        GradientDrawable bg = (GradientDrawable) ContextCompat
                .getDrawable(h.itemView.getContext(), R.drawable.bg_tile).mutate();
        bg.setColor(ContextCompat.getColor(h.itemView.getContext(), t.bg));
        h.itemView.setBackground(bg);

        h.icon.setImageResource(t.icon);
        ImageViewCompat.setImageTintList(h.icon,
                ColorStateList.valueOf(ContextCompat.getColor(h.itemView.getContext(), t.fg)));
        h.name.setText(t.name);
        h.price.setText(t.price);
    }

    @Override
    public int getItemCount() {
        return tiles.size();
    }

    static class ViewHolder extends RecyclerView.ViewHolder {
        ImageView icon;
        TextView name;
        TextView price;

        ViewHolder(@NonNull View itemView) {
            super(itemView);
            icon = itemView.findViewById(R.id.tileIcon);
            name = itemView.findViewById(R.id.tileName);
            price = itemView.findViewById(R.id.tilePrice);
        }
    }
}
